<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;
use Modules\Fitness\Models\Program as ProgramModel;

class Program extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_programs';

    /**
     * @var string
     */
    public string $tablenameAccess = 'fitness_program_access';

    /**
     * Returns programs with their group access and the number of phases and sessions.
     *
     * @param array $where
     * @param array $orderBy
     * @return ProgramModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['position' => 'ASC', 'title' => 'ASC']): array
    {
        $rows = $this->db()->select(['id', 'title', 'teaser', 'description', 'image', 'goal', 'difficulty', 'type', 'access_type', 'price', 'currency', 'status', 'read_access_all', 'position', 'created_at', 'updated_at'])
            ->from($this->tablename)
            ->where($where)
            ->order($orderBy)
            ->execute()
            ->fetchRows();

        if (empty($rows)) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));
        $groups = $this->getGroupIdsPerProgram($ids);
        $phaseCounts = $this->countPerProgram('fitness_program_phases', $ids);
        $sessionCounts = $this->countPerProgram('fitness_program_sessions', $ids);

        $programs = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $programs[] = (new ProgramModel())->setByArray($row)
                ->setGroupIds($groups[$id] ?? [])
                ->setPhaseCount($phaseCounts[$id] ?? 0)
                ->setSessionCount($sessionCounts[$id] ?? 0);
        }

        return $programs;
    }

    /**
     * @return ProgramModel[]
     */
    public function getPrograms(): array
    {
        return $this->getEntriesBy();
    }

    public function getProgramById(int $id): ?ProgramModel
    {
        $programs = $this->getEntriesBy(['id' => $id]);

        return reset($programs) ?: null;
    }

    /**
     * Returns the published programs one of the given groups may see.
     *
     * @param int[] $groupIds groups of the current user, guests are group 3
     * @return ProgramModel[]
     */
    public function getPublishedProgramsForGroups(array $groupIds): array
    {
        return array_values(array_filter(
            $this->getEntriesBy(['status' => ProgramModel::STATUS_PUBLISHED]),
            static fn (ProgramModel $program) => $program->isVisibleForGroups($groupIds)
        ));
    }

    /**
     * Inserts or updates a program together with its group access.
     * New programs are put at the end of the list.
     *
     * @param ProgramModel $program
     * @return int id of the program
     */
    public function save(ProgramModel $program): int
    {
        $now = (new Date())->toDb();

        if ($program->getId()) {
            $program->setUpdatedAt($now);
            $fields = $program->getArray(false);
            unset($fields['created_at'], $fields['position']);
            $this->updateRow($this->tablename, $program->getId(), $fields);

            $id = $program->getId();
        } else {
            if ($program->getCreatedAt() === '') {
                $program->setCreatedAt($now);
            }
            $program->setPosition($this->getNextPositionOf($this->tablename));

            $id = (int)$this->db()->insert($this->tablename)
                ->values($program->getArray(false))
                ->execute();
            $program->setId($id);
        }

        $this->saveGroupIds($id, $program->isReadAccessAll() ? [] : $program->getGroupIds());

        return $id;
    }

    /**
     * Saves the order of the programs.
     *
     * @param int[] $ids program ids in the new order
     */
    public function updatePositions(array $ids): void
    {
        $this->updatePositionsOf($this->tablename, $ids);
    }

    /**
     * Returns whether somebody takes part in the program or ordered it.
     * Such a program can't be deleted, only archived.
     *
     * @param int $id
     * @return bool
     */
    public function hasParticipantsOrOrders(int $id): bool
    {
        foreach (['fitness_enrollments', 'fitness_orders'] as $table) {
            if ($this->db()->select('COUNT(*)')->from($table)->where(['program_id' => $id])->execute()->fetchCell()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes a program without participants and orders, together with its phases and sessions.
     *
     * @param int $id
     * @return bool false if the program has participants or orders
     */
    public function delete(int $id): bool
    {
        if ($this->hasParticipantsOrOrders($id)) {
            return false;
        }

        return (bool)$this->db()->delete($this->tablename)
            ->where(['id' => $id])
            ->execute();
    }

    /**
     * Deletes a program even if it has participants. Their participations and logged sessions are
     * deleted with it. Programs with orders are never deleted, because orders have to stay.
     *
     * @param int $id
     * @return bool false if the program has orders
     */
    public function deleteWithParticipants(int $id): bool
    {
        $hasOrders = $this->db()->select('COUNT(*)')
            ->from('fitness_orders')
            ->where(['program_id' => $id])
            ->execute()
            ->fetchCell();

        if ($hasOrders) {
            return false;
        }

        return (bool)$this->db()->delete($this->tablename)
            ->where(['id' => $id])
            ->execute();
    }

    /**
     * Replaces the groups that may see a program.
     *
     * @param int $programId
     * @param int[] $groupIds
     */
    private function saveGroupIds(int $programId, array $groupIds): void
    {
        $this->db()->delete($this->tablenameAccess)
            ->where(['program_id' => $programId])
            ->execute();

        foreach ($groupIds as $groupId) {
            $this->db()->insert($this->tablenameAccess)
                ->values(['program_id' => $programId, 'group_id' => $groupId])
                ->execute();
        }
    }

    /**
     * @param int[] $programIds
     * @return array<int, int[]> program id => group ids
     */
    private function getGroupIdsPerProgram(array $programIds): array
    {
        $rows = $this->db()->select(['program_id', 'group_id'])
            ->from($this->tablenameAccess)
            ->where(['program_id' => $programIds])
            ->execute()
            ->fetchRows();

        $groups = [];
        foreach ($rows as $row) {
            $groups[(int)$row['program_id']][] = (int)$row['group_id'];
        }

        return $groups;
    }

    /**
     * @param string $table table with a program_id column
     * @param int[] $programIds
     * @return array<int, int> program id => number of rows
     */
    private function countPerProgram(string $table, array $programIds): array
    {
        $rows = $this->db()->select(['program_id', 'row_count' => 'COUNT(*)'])
            ->from($table)
            ->where(['program_id' => $programIds])
            ->group(['program_id'])
            ->execute()
            ->fetchRows();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['program_id']] = (int)$row['row_count'];
        }

        return $counts;
    }
}
