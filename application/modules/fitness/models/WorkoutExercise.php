<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * An exercise inside a workout with its own values for sets, reps and so on.
 * The exercise itself is only referenced and never copied.
 */
class WorkoutExercise extends Model
{
    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var int
     */
    protected int $workoutId = 0;

    /**
     * @var int
     */
    protected int $exerciseId = 0;

    /**
     * Title of the exercise. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $exerciseTitle = '';

    /**
     * Whether the referenced exercise is active. Only filled when loaded by the mapper.
     *
     * @var bool
     */
    protected bool $exerciseActive = true;

    /**
     * @var int
     */
    protected int $position = 0;

    /**
     * @var int|null
     */
    protected ?int $sets = null;

    /**
     * @var int|null
     */
    protected ?int $repsMin = null;

    /**
     * @var int|null
     */
    protected ?int $repsMax = null;

    /**
     * Free text like "20 kg" or "70 % 1RM".
     *
     * @var string
     */
    protected string $weight = '';

    /**
     * @var int|null
     */
    protected ?int $durationSec = null;

    /**
     * @var int|null
     */
    protected ?int $restSec = null;

    /**
     * @var string
     */
    protected string $notes = '';

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): WorkoutExercise
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['workout_id'])) {
            $this->setWorkoutId($entries['workout_id']);
        }
        if (isset($entries['exercise_id'])) {
            $this->setExerciseId($entries['exercise_id']);
        }
        if (isset($entries['exercise_title'])) {
            $this->setExerciseTitle($entries['exercise_title']);
        }
        if (isset($entries['exercise_active'])) {
            $this->setExerciseActive($entries['exercise_active']);
        }
        if (isset($entries['position'])) {
            $this->setPosition($entries['position']);
        }
        if (array_key_exists('sets', $entries)) {
            $this->setSets(self::toNullableInt($entries['sets']));
        }
        if (array_key_exists('reps_min', $entries)) {
            $this->setRepsMin(self::toNullableInt($entries['reps_min']));
        }
        if (array_key_exists('reps_max', $entries)) {
            $this->setRepsMax(self::toNullableInt($entries['reps_max']));
        }
        if (isset($entries['weight'])) {
            $this->setWeight($entries['weight']);
        }
        if (array_key_exists('duration_sec', $entries)) {
            $this->setDurationSec(self::toNullableInt($entries['duration_sec']));
        }
        if (array_key_exists('rest_sec', $entries)) {
            $this->setRestSec(self::toNullableInt($entries['rest_sec']));
        }
        if (isset($entries['notes'])) {
            $this->setNotes($entries['notes']);
        }

        return $this;
    }

    /**
     * Turns '' and null into null, everything else into an int.
     *
     * @param mixed $value
     * @return int|null
     */
    public static function toNullableInt($value): ?int
    {
        return ($value === null || $value === '') ? null : (int)$value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WorkoutExercise
    {
        $this->id = $id;
        return $this;
    }

    public function getWorkoutId(): int
    {
        return $this->workoutId;
    }

    public function setWorkoutId(int $workoutId): WorkoutExercise
    {
        $this->workoutId = $workoutId;
        return $this;
    }

    public function getExerciseId(): int
    {
        return $this->exerciseId;
    }

    public function setExerciseId(int $exerciseId): WorkoutExercise
    {
        $this->exerciseId = $exerciseId;
        return $this;
    }

    public function getExerciseTitle(): string
    {
        return $this->exerciseTitle;
    }

    public function setExerciseTitle(string $exerciseTitle): WorkoutExercise
    {
        $this->exerciseTitle = $exerciseTitle;
        return $this;
    }

    public function isExerciseActive(): bool
    {
        return $this->exerciseActive;
    }

    public function setExerciseActive(bool $exerciseActive): WorkoutExercise
    {
        $this->exerciseActive = $exerciseActive;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): WorkoutExercise
    {
        $this->position = $position;
        return $this;
    }

    public function getSets(): ?int
    {
        return $this->sets;
    }

    public function setSets(?int $sets): WorkoutExercise
    {
        $this->sets = $sets;
        return $this;
    }

    public function getRepsMin(): ?int
    {
        return $this->repsMin;
    }

    public function setRepsMin(?int $repsMin): WorkoutExercise
    {
        $this->repsMin = $repsMin;
        return $this;
    }

    public function getRepsMax(): ?int
    {
        return $this->repsMax;
    }

    public function setRepsMax(?int $repsMax): WorkoutExercise
    {
        $this->repsMax = $repsMax;
        return $this;
    }

    /**
     * Returns the reps as text, for example "10" or "8–12". Empty if no reps are set.
     *
     * @return string
     */
    public function getRepsText(): string
    {
        if ($this->repsMin === null && $this->repsMax === null) {
            return '';
        }

        if ($this->repsMin === null || $this->repsMax === null || $this->repsMin === $this->repsMax) {
            return (string)($this->repsMin ?? $this->repsMax);
        }

        return $this->repsMin . '–' . $this->repsMax;
    }

    public function getWeight(): string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): WorkoutExercise
    {
        $this->weight = $weight;
        return $this;
    }

    public function getDurationSec(): ?int
    {
        return $this->durationSec;
    }

    public function setDurationSec(?int $durationSec): WorkoutExercise
    {
        $this->durationSec = $durationSec;
        return $this;
    }

    public function getRestSec(): ?int
    {
        return $this->restSec;
    }

    public function setRestSec(?int $restSec): WorkoutExercise
    {
        $this->restSec = $restSec;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): WorkoutExercise
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Returns the fields of the workout_exercises table.
     *
     * @param bool $withId
     * @return array
     */
    public function getArray(bool $withId = true): array
    {
        return array_merge(
            ($withId ? ['id' => $this->getId()] : []),
            [
                'workout_id' => $this->getWorkoutId(),
                'exercise_id' => $this->getExerciseId(),
                'position' => $this->getPosition(),
                'sets' => $this->getSets(),
                'reps_min' => $this->getRepsMin(),
                'reps_max' => $this->getRepsMax(),
                'weight' => $this->getWeight(),
                'duration_sec' => $this->getDurationSec(),
                'rest_sec' => $this->getRestSec(),
                'notes' => $this->getNotes(),
            ]
        );
    }
}
