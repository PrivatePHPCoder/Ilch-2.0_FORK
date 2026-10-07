<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Modules\Fitness\Models\WorkoutExercise as WorkoutExerciseModel;

class WorkoutExercise extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_workout_exercises';

    /**
     * Returns the exercises of a workout in their order.
     *
     * @param int $workoutId
     * @return WorkoutExerciseModel[]
     */
    public function getExercisesOfWorkout(int $workoutId): array
    {
        $rows = $this->db()->select(['we.id', 'we.workout_id', 'we.exercise_id', 'we.position', 'we.sets', 'we.reps_min', 'we.reps_max', 'we.weight', 'we.duration_sec', 'we.rest_sec', 'we.notes'])
            ->from(['we' => $this->tablename])
            ->join(['e' => 'fitness_exercises'], 'e.id = we.exercise_id', 'INNER', ['exercise_title' => 'e.title', 'exercise_active' => 'e.active'])
            ->where(['we.workout_id' => $workoutId])
            ->order(['we.position' => 'ASC', 'we.id' => 'ASC'])
            ->execute()
            ->fetchRows();

        $exercises = [];
        foreach ($rows as $row) {
            $exercises[] = (new WorkoutExerciseModel())->setByArray($row);
        }

        return $exercises;
    }

    /**
     * Stores the exercises of a workout in the given order.
     *
     * Rows that already belong to the workout are updated and keep their id, new rows are
     * inserted and rows that are no longer in the list are deleted. Stable ids matter for
     * training logs that refer to a single exercise of a workout.
     *
     * @param int $workoutId
     * @param WorkoutExerciseModel[] $exercises in their order
     */
    public function syncExercisesOfWorkout(int $workoutId, array $exercises): void
    {
        $existingIds = array_map('intval', $this->db()->select('id')
            ->from($this->tablename)
            ->where(['workout_id' => $workoutId])
            ->execute()
            ->fetchList());

        $keptIds = [];
        foreach (array_values($exercises) as $position => $exercise) {
            $exercise->setWorkoutId($workoutId)
                ->setPosition($position);

            if ($exercise->getId() && in_array($exercise->getId(), $existingIds, true)) {
                $this->updateRow($this->tablename, $exercise->getId(), $exercise->getArray(false));
                $keptIds[] = $exercise->getId();
            } else {
                $exercise->setId((int)$this->db()->insert($this->tablename)
                    ->values($exercise->getArray(false))
                    ->execute());
            }
        }

        $removedIds = array_values(array_diff($existingIds, $keptIds));
        if ($removedIds) {
            $this->db()->delete($this->tablename)
                ->where(['workout_id' => $workoutId, 'id' => $removedIds])
                ->execute();
        }
    }

    /**
     * Returns the number of exercises per workout.
     *
     * @return array<int, int> workout id => number of exercises
     */
    public function getCountsPerWorkout(): array
    {
        $rows = $this->db()->select(['workout_id', 'exercise_count' => 'COUNT(*)'])
            ->from($this->tablename)
            ->group(['workout_id'])
            ->execute()
            ->fetchRows();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['workout_id']] = (int)$row['exercise_count'];
        }

        return $counts;
    }
}
