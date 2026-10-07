<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;
use Ilch\Mapper;
use Modules\Fitness\Models\Exercise as ExerciseModel;

class Exercise extends Mapper
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_exercises';

    /**
     * @var string
     */
    public string $tablenameMuscles = 'fitness_exercise_muscles';

    /**
     * Returns exercises including category name and muscle groups.
     *
     * @param array $where
     * @param array $orderBy
     * @return ExerciseModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['e.position' => 'ASC', 'e.title' => 'ASC']): array
    {
        $rows = $this->db()->select(['e.id', 'e.category_id', 'e.title', 'e.description', 'e.instructions', 'e.notes', 'e.difficulty', 'e.image', 'e.video_url', 'e.is_public', 'e.active', 'e.position', 'e.created_at', 'e.updated_at'])
            ->from(['e' => $this->tablename])
            ->join(['c' => 'fitness_categories'], 'c.id = e.category_id', 'LEFT', ['category_name' => 'c.name'])
            ->where($where)
            ->order($orderBy)
            ->execute()
            ->fetchRows();

        if (empty($rows)) {
            return [];
        }

        $muscleRows = $this->db()->select(['exercise_id', 'muscle_group_id', 'is_primary'])
            ->from($this->tablenameMuscles)
            ->where(['exercise_id' => array_column($rows, 'id')])
            ->execute()
            ->fetchRows();

        $muscles = [];
        foreach ($muscleRows as $muscleRow) {
            $muscles[$muscleRow['exercise_id']]['ids'][] = (int)$muscleRow['muscle_group_id'];
            if ($muscleRow['is_primary']) {
                $muscles[$muscleRow['exercise_id']]['primary'] = (int)$muscleRow['muscle_group_id'];
            }
        }

        $exercises = [];
        foreach ($rows as $row) {
            $exercise = (new ExerciseModel())->setByArray($row);
            $exercise->setMuscleGroups($muscles[$row['id']]['ids'] ?? [], $muscles[$row['id']]['primary'] ?? null);
            $exercises[] = $exercise;
        }

        return $exercises;
    }

    /**
     * @return ExerciseModel[]
     */
    public function getExercises(): array
    {
        return $this->getEntriesBy();
    }

    public function getExerciseById(int $id): ?ExerciseModel
    {
        $exercises = $this->getEntriesBy(['e.id' => $id]);

        return reset($exercises) ?: null;
    }

    /**
     * Inserts or updates an exercise together with its muscle groups.
     * New exercises are put at the end of the list.
     *
     * @param ExerciseModel $exercise
     * @return int id of the exercise
     */
    public function save(ExerciseModel $exercise): int
    {
        $now = (new Date())->toDb();

        if ($exercise->getId()) {
            $exercise->setUpdatedAt($now);
            $fields = $exercise->getArray(false);
            unset($fields['created_at']);

            $this->db()->update($this->tablename)
                ->values($fields)
                ->where(['id' => $exercise->getId()])
                ->execute();

            if ($exercise->getCategoryId() === null) {
                // The query builder skips null values, so remove the category explicitly.
                $this->db()->query('UPDATE `[prefix]_' . $this->tablename . '` SET `category_id` = NULL WHERE `id` = ' . $exercise->getId());
            }

            $id = $exercise->getId();
        } else {
            if ($exercise->getCreatedAt() === '') {
                $exercise->setCreatedAt($now);
            }
            $exercise->setPosition($this->getNextPosition());

            $id = (int)$this->db()->insert($this->tablename)
                ->values($exercise->getArray(false))
                ->execute();
        }

        $this->saveMuscleGroups($id, $exercise->getMuscleGroupIds(), $exercise->getPrimaryMuscleGroupId());

        return $id;
    }

    /**
     * Replaces the muscle groups of an exercise.
     *
     * @param int $exerciseId
     * @param int[] $muscleGroupIds
     * @param int|null $primaryMuscleGroupId
     */
    public function saveMuscleGroups(int $exerciseId, array $muscleGroupIds, ?int $primaryMuscleGroupId): void
    {
        $this->db()->delete($this->tablenameMuscles)
            ->where(['exercise_id' => $exerciseId])
            ->execute();

        foreach ($muscleGroupIds as $muscleGroupId) {
            $this->db()->insert($this->tablenameMuscles)
                ->values([
                    'exercise_id' => $exerciseId,
                    'muscle_group_id' => $muscleGroupId,
                    'is_primary' => (int)($muscleGroupId === $primaryMuscleGroupId),
                ])
                ->execute();
        }
    }

    /**
     * Saves the order of the exercises.
     *
     * @param int[] $ids exercise ids in the new order
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
     * Returns whether a workout uses this exercise. Such an exercise can't be deleted.
     *
     * @param int $id
     * @return bool
     */
    public function isUsedInWorkouts(int $id): bool
    {
        return (bool)$this->db()->select('COUNT(*)')
            ->from('fitness_workout_exercises')
            ->where(['exercise_id' => $id])
            ->execute()
            ->fetchCell();
    }

    /**
     * Deletes an exercise that is not used by any workout.
     *
     * @param int $id
     * @return bool false if the exercise is still used by a workout
     */
    public function delete(int $id): bool
    {
        if ($this->isUsedInWorkouts($id)) {
            return false;
        }

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
