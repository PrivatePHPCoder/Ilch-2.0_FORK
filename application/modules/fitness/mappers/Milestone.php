<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;
use Modules\Fitness\Models\Milestone as MilestoneModel;

/**
 * Milestones and the milestones users have reached.
 */
class Milestone extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_milestones';

    /**
     * @var string
     */
    public string $tablenameAchieved = 'fitness_user_milestones';

    /**
     * Milestones that are created on installation. They have no own title, so the title
     * is shown in the language of the visitor.
     *
     * @var array<int, array{type: string, threshold: int, icon: string}>
     */
    public const DEFAULTS = [
        ['type' => MilestoneModel::TYPE_SESSIONS, 'threshold' => 1, 'icon' => 'fa-solid fa-flag-checkered'],
        ['type' => MilestoneModel::TYPE_SESSIONS, 'threshold' => 5, 'icon' => 'fa-solid fa-fire'],
        ['type' => MilestoneModel::TYPE_SESSIONS, 'threshold' => 10, 'icon' => 'fa-solid fa-bolt'],
        ['type' => MilestoneModel::TYPE_SESSIONS, 'threshold' => 25, 'icon' => 'fa-solid fa-dumbbell'],
        ['type' => MilestoneModel::TYPE_PHASES, 'threshold' => 1, 'icon' => 'fa-solid fa-star'],
        ['type' => MilestoneModel::TYPE_PERCENT, 'threshold' => 50, 'icon' => 'fa-solid fa-mountain'],
        ['type' => MilestoneModel::TYPE_PROGRAMS, 'threshold' => 1, 'icon' => 'fa-solid fa-trophy'],
    ];

    /**
     * Returns milestones with the title of their program and how often they were reached.
     *
     * @param array $where
     * @param array $orderBy
     * @return MilestoneModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['m.position' => 'ASC', 'm.id' => 'ASC']): array
    {
        $rows = $this->db()->select(['m.id', 'm.program_id', 'm.type', 'm.threshold', 'm.title', 'm.description', 'm.icon', 'm.position', 'm.active'])
            ->from(['m' => $this->tablename])
            ->join(['p' => 'fitness_programs'], 'p.id = m.program_id', 'LEFT', ['program_title' => 'p.title'])
            ->where($where)
            ->order($orderBy)
            ->execute()
            ->fetchRows();

        $counts = $rows ? $this->getAchievedCounts() : [];

        $milestones = [];
        foreach ($rows as $row) {
            $milestones[] = (new MilestoneModel())
                ->setByArray($row)
                ->setAchievedCount($counts[(int)$row['id']] ?? 0);
        }

        return $milestones;
    }

    /**
     * @return MilestoneModel[]
     */
    public function getMilestones(): array
    {
        return $this->getEntriesBy();
    }

    /**
     * @return MilestoneModel[]
     */
    public function getActiveMilestones(): array
    {
        return $this->getEntriesBy(['m.active' => 1]);
    }

    public function getMilestoneById(int $id): ?MilestoneModel
    {
        $milestones = $this->getEntriesBy(['m.id' => $id]);

        return reset($milestones) ?: null;
    }

    /**
     * Inserts or updates a milestone. New milestones are put at the end of the list.
     *
     * @param MilestoneModel $milestone
     * @return int id of the milestone
     */
    public function save(MilestoneModel $milestone): int
    {
        if ($milestone->getId()) {
            $this->updateRow($this->tablename, $milestone->getId(), $milestone->getArray(false));

            return $milestone->getId();
        }

        $milestone->setPosition($this->getNextPositionOf($this->tablename));

        return (int)$this->db()->insert($this->tablename)
            ->values($milestone->getArray(false))
            ->execute();
    }

    /**
     * Saves the order of the milestones.
     *
     * @param int[] $ids milestone ids in the new order
     */
    public function updatePositions(array $ids): void
    {
        $this->updatePositionsOf($this->tablename, $ids);
    }

    /**
     * Deletes a milestone. Users who reached it lose it as well.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return (bool)$this->db()->delete($this->tablename)
            ->where(['id' => $id])
            ->execute();
    }

    /**
     * Creates the default milestones, but only if there are no milestones yet.
     *
     * @return int number of created milestones
     */
    public function createDefaults(): int
    {
        if ($this->db()->select('COUNT(*)')->from($this->tablename)->execute()->fetchCell()) {
            return 0;
        }

        foreach (self::DEFAULTS as $default) {
            $this->save((new MilestoneModel())
                ->setType($default['type'])
                ->setThreshold($default['threshold'])
                ->setIcon($default['icon']));
        }

        return count(self::DEFAULTS);
    }

    /**
     * Returns how often each milestone was reached.
     *
     * @return array<int, int> milestone id => number of users
     */
    public function getAchievedCounts(): array
    {
        $rows = $this->db()->select(['milestone_id', 'achieved_count' => 'COUNT(*)'])
            ->from($this->tablenameAchieved)
            ->group(['milestone_id'])
            ->execute()
            ->fetchRows();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['milestone_id']] = (int)$row['achieved_count'];
        }

        return $counts;
    }

    /**
     * Returns the milestones a user has reached, newest first.
     *
     * @param int $userId
     * @return array<int, string> milestone id => date and time it was reached
     */
    public function getAchievementsOfUser(int $userId): array
    {
        $rows = $this->db()->select(['milestone_id', 'achieved_at'])
            ->from($this->tablenameAchieved)
            ->where(['user_id' => $userId])
            ->order(['achieved_at' => 'DESC', 'id' => 'DESC'])
            ->execute()
            ->fetchRows();

        $achievements = [];
        foreach ($rows as $row) {
            $achievements[(int)$row['milestone_id']] = $row['achieved_at'];
        }

        return $achievements;
    }

    /**
     * Stores that a user has reached a milestone. A milestone can only be reached once.
     *
     * @param int $milestoneId
     * @param int $userId
     * @param int|null $enrollmentId the participation in which it was reached
     * @return bool false if the user had already reached it
     */
    public function award(int $milestoneId, int $userId, ?int $enrollmentId = null): bool
    {
        $exists = $this->db()->select('COUNT(*)')
            ->from($this->tablenameAchieved)
            ->where(['milestone_id' => $milestoneId, 'user_id' => $userId])
            ->execute()
            ->fetchCell();

        if ($exists) {
            return false;
        }

        try {
            $this->db()->insert($this->tablenameAchieved)
                ->values([
                    'milestone_id' => $milestoneId,
                    'user_id' => $userId,
                    'enrollment_id' => $enrollmentId,
                    'achieved_at' => (new Date())->toDb(),
                ])
                ->execute();
        } catch (\Exception $exception) {
            // A second request was faster (unique milestone and user).
            return false;
        }

        return true;
    }
}
