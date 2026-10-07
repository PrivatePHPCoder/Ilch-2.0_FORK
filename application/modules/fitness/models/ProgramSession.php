<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * A training session inside a phase of a program. It refers to a workout.
 */
class ProgramSession extends Model
{
    /**
     * Translation keys of the week days used as day hint.
     *
     * @var array<int, string>
     */
    public const DAYS = [
        1 => 'dayMonday',
        2 => 'dayTuesday',
        3 => 'dayWednesday',
        4 => 'dayThursday',
        5 => 'dayFriday',
        6 => 'daySaturday',
        7 => 'daySunday',
    ];

    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var int
     */
    protected int $programId = 0;

    /**
     * @var int
     */
    protected int $phaseId = 0;

    /**
     * @var int
     */
    protected int $workoutId = 0;

    /**
     * Title of the workout. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $workoutTitle = '';

    /**
     * @var int
     */
    protected int $position = 0;

    /**
     * For example "Training A".
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Suggested week day, 1 = Monday ... 7 = Sunday.
     *
     * @var int|null
     */
    protected ?int $dayHint = null;

    /**
     * Optional sessions don't count for the progress.
     *
     * @var bool
     */
    protected bool $optional = false;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): ProgramSession
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['program_id'])) {
            $this->setProgramId($entries['program_id']);
        }
        if (isset($entries['phase_id'])) {
            $this->setPhaseId($entries['phase_id']);
        }
        if (isset($entries['workout_id'])) {
            $this->setWorkoutId($entries['workout_id']);
        }
        if (isset($entries['workout_title'])) {
            $this->setWorkoutTitle($entries['workout_title']);
        }
        if (isset($entries['position'])) {
            $this->setPosition($entries['position']);
        }
        if (isset($entries['title'])) {
            $this->setTitle($entries['title']);
        }
        if (array_key_exists('day_hint', $entries)) {
            $this->setDayHint(WorkoutExercise::toNullableInt($entries['day_hint']));
        }
        if (isset($entries['is_optional'])) {
            $this->setOptional($entries['is_optional']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ProgramSession
    {
        $this->id = $id;
        return $this;
    }

    public function getProgramId(): int
    {
        return $this->programId;
    }

    public function setProgramId(int $programId): ProgramSession
    {
        $this->programId = $programId;
        return $this;
    }

    public function getPhaseId(): int
    {
        return $this->phaseId;
    }

    public function setPhaseId(int $phaseId): ProgramSession
    {
        $this->phaseId = $phaseId;
        return $this;
    }

    public function getWorkoutId(): int
    {
        return $this->workoutId;
    }

    public function setWorkoutId(int $workoutId): ProgramSession
    {
        $this->workoutId = $workoutId;
        return $this;
    }

    public function getWorkoutTitle(): string
    {
        return $this->workoutTitle;
    }

    public function setWorkoutTitle(string $workoutTitle): ProgramSession
    {
        $this->workoutTitle = $workoutTitle;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): ProgramSession
    {
        $this->position = $position;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ProgramSession
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns the title of the session or, if empty, the title of the workout.
     *
     * @return string
     */
    public function getDisplayTitle(): string
    {
        return $this->title !== '' ? $this->title : $this->workoutTitle;
    }

    public function getDayHint(): ?int
    {
        return $this->dayHint;
    }

    /**
     * @param int|null $dayHint 1 = Monday ... 7 = Sunday, everything else means no day
     * @return $this
     */
    public function setDayHint(?int $dayHint): ProgramSession
    {
        $this->dayHint = isset(self::DAYS[$dayHint]) ? $dayHint : null;
        return $this;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function setOptional(bool $optional): ProgramSession
    {
        $this->optional = $optional;
        return $this;
    }

    /**
     * Returns the fields of the program_sessions table.
     *
     * @param bool $withId
     * @return array
     */
    public function getArray(bool $withId = true): array
    {
        return array_merge(
            ($withId ? ['id' => $this->getId()] : []),
            [
                'program_id' => $this->getProgramId(),
                'phase_id' => $this->getPhaseId(),
                'workout_id' => $this->getWorkoutId(),
                'position' => $this->getPosition(),
                'title' => $this->getTitle(),
                'day_hint' => $this->getDayHint(),
                'is_optional' => (int)$this->isOptional(),
            ]
        );
    }
}
