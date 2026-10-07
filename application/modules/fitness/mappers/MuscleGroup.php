<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Mapper;
use Modules\Fitness\Models\MuscleGroup as MuscleGroupModel;

class MuscleGroup extends Mapper
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_muscle_groups';

    /**
     * Returns muscle groups with the number of exercises that train them.
     *
     * @param array $where
     * @param array $orderBy
     * @return MuscleGroupModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['m.position' => 'ASC', 'm.name' => 'ASC']): array
    {
        $rows = $this->db()->select(['m.id', 'm.name', 'm.position'])
            ->from(['m' => $this->tablename])
            ->join(['em' => 'fitness_exercise_muscles'], 'em.muscle_group_id = m.id', 'LEFT', ['exercise_count' => 'COUNT(em.exercise_id)'])
            ->where($where)
            ->group(['m.id', 'm.name', 'm.position'])
            ->order($orderBy)
            ->execute()
            ->fetchRows();

        $muscleGroups = [];
        foreach ($rows as $row) {
            $muscleGroups[] = (new MuscleGroupModel())->setByArray($row);
        }

        return $muscleGroups;
    }

    /**
     * @return MuscleGroupModel[]
     */
    public function getMuscleGroups(): array
    {
        return $this->getEntriesBy();
    }

    public function getMuscleGroupById(int $id): ?MuscleGroupModel
    {
        $muscleGroups = $this->getEntriesBy(['m.id' => $id]);

        return reset($muscleGroups) ?: null;
    }

    /**
     * Inserts or updates a muscle group. New muscle groups are put at the end of the list.
     *
     * @param MuscleGroupModel $muscleGroup
     * @return int id of the muscle group
     */
    public function save(MuscleGroupModel $muscleGroup): int
    {
        if ($muscleGroup->getId()) {
            $this->db()->update($this->tablename)
                ->values($muscleGroup->getArray(false))
                ->where(['id' => $muscleGroup->getId()])
                ->execute();

            return $muscleGroup->getId();
        }

        $muscleGroup->setPosition($this->getNextPosition());

        return (int)$this->db()->insert($this->tablename)
            ->values($muscleGroup->getArray(false))
            ->execute();
    }

    /**
     * Saves the order of the muscle groups.
     *
     * @param int[] $ids muscle group ids in the new order
     */
    public function updatePositions(array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            $this->db()->update($this->tablename)
                ->values(['position' => $position])
                ->where(['id' => (int)$id])
                ->execute();
        }
    }

    /**
     * Deletes a muscle group. It is removed from all exercises.
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

    private function getNextPosition(): int
    {
        return (int)$this->db()->select('MAX(position)')
            ->from($this->tablename)
            ->execute()
            ->fetchCell() + 1;
    }
}
