<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * A reusable training session made of several exercises.
 */
class Workout extends Model
{
    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * Estimated duration in minutes.
     *
     * @var int|null
     */
    protected ?int $durationMin = null;

    /**
     * @var int
     */
    protected int $difficulty = Difficulty::BEGINNER;

    /**
     * @var bool
     */
    protected bool $active = true;

    /**
     * @var string
     */
    protected string $createdAt = '';

    /**
     * @var string|null
     */
    protected ?string $updatedAt = null;

    /**
     * Number of exercises. Only filled when loaded by the mapper.
     *
     * @var int
     */
    protected int $exerciseCount = 0;

    /**
     * Exercises in their order. Only filled when loaded with exercises.
     *
     * @var WorkoutExercise[]
     */
    protected array $exercises = [];

    /**
     * Whether the exercises were set. Only then they are stored when the workout is saved.
     *
     * @var bool
     */
    protected bool $exercisesSet = false;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Workout
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['title'])) {
            $this->setTitle($entries['title']);
        }
        if (isset($entries['description'])) {
            $this->setDescription($entries['description']);
        }
        if (array_key_exists('duration_min', $entries)) {
            $this->setDurationMin(WorkoutExercise::toNullableInt($entries['duration_min']));
        }
        if (isset($entries['difficulty'])) {
            $this->setDifficulty($entries['difficulty']);
        }
        if (isset($entries['active'])) {
            $this->setActive($entries['active']);
        }
        if (isset($entries['created_at'])) {
            $this->setCreatedAt($entries['created_at']);
        }
        if (array_key_exists('updated_at', $entries)) {
            $this->setUpdatedAt($entries['updated_at']);
        }
        if (isset($entries['exercise_count'])) {
            $this->setExerciseCount($entries['exercise_count']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Workout
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Workout
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Workout
    {
        $this->description = $description;
        return $this;
    }

    public function getDurationMin(): ?int
    {
        return $this->durationMin;
    }

    public function setDurationMin(?int $durationMin): Workout
    {
        $this->durationMin = $durationMin;
        return $this;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): Workout
    {
        $this->difficulty = Difficulty::normalize($difficulty);
        return $this;
    }

    /**
     * Returns the translation key of the difficulty.
     *
     * @return string
     */
    public function getDifficultyKey(): string
    {
        return Difficulty::getKey($this->difficulty);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): Workout
    {
        $this->active = $active;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): Workout
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): Workout
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getExerciseCount(): int
    {
        return $this->exerciseCount;
    }

    public function setExerciseCount(int $exerciseCount): Workout
    {
        $this->exerciseCount = $exerciseCount;
        return $this;
    }

    /**
     * @return WorkoutExercise[]
     */
    public function getExercises(): array
    {
        return $this->exercises;
    }

    /**
     * @param WorkoutExercise[] $exercises in their order
     * @return $this
     */
    public function setExercises(array $exercises): Workout
    {
        $this->exercises = array_values($exercises);
        $this->exerciseCount = count($this->exercises);
        $this->exercisesSet = true;
        return $this;
    }

    /**
     * Returns whether the exercises were set (loaded or assigned).
     * A workout loaded without exercises keeps its stored exercises when saved.
     *
     * @return bool
     */
    public function areExercisesSet(): bool
    {
        return $this->exercisesSet;
    }

    /**
     * Returns the fields of the workouts table. Exercises are stored separately.
     *
     * @param bool $withId
     * @return array
     */
    public function getArray(bool $withId = true): array
    {
        return array_merge(
            ($withId ? ['id' => $this->getId()] : []),
            [
                'title' => $this->getTitle(),
                'description' => $this->getDescription(),
                'duration_min' => $this->getDurationMin(),
                'difficulty' => $this->getDifficulty(),
                'active' => (int)$this->isActive(),
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
            ]
        );
    }
}
