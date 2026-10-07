<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;
use Modules\Fitness\Models\Workout as WorkoutModel;

class Workout extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_workouts';

    /**
     * Returns workouts with the number of their exercises.
     *
     * @param array $where
     * @param array $orderBy
     * @return WorkoutModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['title' => 'ASC']): array
    {
        $rows = $this->db()->select(['id', 'title', 'description', 'duration_min', 'difficulty', 'active', 'created_at', 'updated_at'])
            ->from($this->tablename)
            ->where($where)
            ->order($orderBy)
            ->execute()
            ->fetchRows();

        $counts = (new WorkoutExercise())->getCountsPerWorkout();

        $workouts = [];
        foreach ($rows as $row) {
            $workouts[] = (new WorkoutModel())->setByArray($row)
                ->setExerciseCount($counts[(int)$row['id']] ?? 0);
        }

        return $workouts;
    }

    /**
     * @return WorkoutModel[]
     */
    public function getWorkouts(): array
    {
        return $this->getEntriesBy();
    }

    /**
     * Returns a workout. With $withExercises its exercises are loaded as well.
     *
     * @param int $id
     * @param bool $withExercises
     * @return WorkoutModel|null
     */
    public function getWorkoutById(int $id, bool $withExercises = true): ?WorkoutModel
    {
        $workouts = $this->getEntriesBy(['id' => $id]);
        $workout = reset($workouts) ?: null;

        if ($workout && $withExercises) {
            $workout->setExercises((new WorkoutExercise())->getExercisesOfWorkout($id));
        }

        return $workout;
    }

    /**
     * Inserts or updates a workout. Its exercises are stored too, if they were set.
     *
     * @param WorkoutModel $workout
     * @return int id of the workout
     */
    public function save(WorkoutModel $workout): int
    {
        $now = (new Date())->toDb();

        if ($workout->getId()) {
            $workout->setUpdatedAt($now);
            $fields = $workout->getArray(false);
            unset($fields['created_at']);
            $this->updateRow($this->tablename, $workout->getId(), $fields);

            $id = $workout->getId();
        } else {
            if ($workout->getCreatedAt() === '') {
                $workout->setCreatedAt($now);
            }

            $id = (int)$this->db()->insert($this->tablename)
                ->values($workout->getArray(false))
                ->execute();
            $workout->setId($id);
        }

        if ($workout->areExercisesSet()) {
            (new WorkoutExercise())->syncExercisesOfWorkout($id, $workout->getExercises());
        }

        return $id;
    }

    /**
     * Returns whether a program uses this workout. Such a workout can't be deleted.
     *
     * @param int $id
     * @return bool
     */
    public function isUsedInPrograms(int $id): bool
    {
        return (bool)$this->db()->select('COUNT(*)')
            ->from('fitness_program_sessions')
            ->where(['workout_id' => $id])
            ->execute()
            ->fetchCell();
    }

    /**
     * Deletes a workout that is not used by any program. Its exercise rows go with it,
     * the exercises themselves stay.
     *
     * @param int $id
     * @return bool false if the workout is still used by a program
     */
    public function delete(int $id): bool
    {
        if ($this->isUsedInPrograms($id)) {
            return false;
        }

        return (bool)$this->db()->delete($this->tablename)
            ->where(['id' => $id])
            ->execute();
    }
}
