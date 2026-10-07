<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

class Exercise extends Model
{
    public const DIFFICULTY_BEGINNER = 1;
    public const DIFFICULTY_INTERMEDIATE = 2;
    public const DIFFICULTY_ADVANCED = 3;

    /**
     * Translation keys of the difficulty levels.
     *
     * @var array<int, string>
     */
    public const DIFFICULTIES = [
        self::DIFFICULTY_BEGINNER => 'difficultyBeginner',
        self::DIFFICULTY_INTERMEDIATE => 'difficultyIntermediate',
        self::DIFFICULTY_ADVANCED => 'difficultyAdvanced',
    ];

    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var int|null
     */
    protected ?int $categoryId = null;

    /**
     * Name of the category. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $categoryName = '';

    /**
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * Step by step instructions.
     *
     * @var string
     */
    protected string $instructions = '';

    /**
     * Notes like safety hints. Plain text.
     *
     * @var string
     */
    protected string $notes = '';

    /**
     * @var int
     */
    protected int $difficulty = self::DIFFICULTY_BEGINNER;

    /**
     * Path or URL of the image.
     *
     * @var string
     */
    protected string $image = '';

    /**
     * @var string
     */
    protected string $videoUrl = '';

    /**
     * Shown in the public exercise library.
     *
     * @var bool
     */
    protected bool $public = false;

    /**
     * @var bool
     */
    protected bool $active = true;

    /**
     * @var int
     */
    protected int $position = 0;

    /**
     * @var string
     */
    protected string $createdAt = '';

    /**
     * @var string|null
     */
    protected ?string $updatedAt = null;

    /**
     * Ids of all trained muscle groups, including the primary one.
     *
     * @var int[]
     */
    protected array $muscleGroupIds = [];

    /**
     * @var int|null
     */
    protected ?int $primaryMuscleGroupId = null;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Exercise
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (array_key_exists('category_id', $entries)) {
            $this->setCategoryId($entries['category_id'] !== null ? (int)$entries['category_id'] : null);
        }
        if (isset($entries['category_name'])) {
            $this->setCategoryName($entries['category_name']);
        }
        if (isset($entries['title'])) {
            $this->setTitle($entries['title']);
        }
        if (isset($entries['description'])) {
            $this->setDescription($entries['description']);
        }
        if (isset($entries['instructions'])) {
            $this->setInstructions($entries['instructions']);
        }
        if (isset($entries['notes'])) {
            $this->setNotes($entries['notes']);
        }
        if (isset($entries['difficulty'])) {
            $this->setDifficulty($entries['difficulty']);
        }
        if (isset($entries['image'])) {
            $this->setImage($entries['image']);
        }
        if (isset($entries['video_url'])) {
            $this->setVideoUrl($entries['video_url']);
        }
        if (isset($entries['is_public'])) {
            $this->setPublic($entries['is_public']);
        }
        if (isset($entries['active'])) {
            $this->setActive($entries['active']);
        }
        if (isset($entries['position'])) {
            $this->setPosition($entries['position']);
        }
        if (isset($entries['created_at'])) {
            $this->setCreatedAt($entries['created_at']);
        }
        if (array_key_exists('updated_at', $entries)) {
            $this->setUpdatedAt($entries['updated_at']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Exercise
    {
        $this->id = $id;
        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * @param int|null $categoryId 0 or null means no category.
     * @return $this
     */
    public function setCategoryId(?int $categoryId): Exercise
    {
        $this->categoryId = $categoryId ?: null;
        return $this;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): Exercise
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Exercise
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Exercise
    {
        $this->description = $description;
        return $this;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): Exercise
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): Exercise
    {
        $this->notes = $notes;
        return $this;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    /**
     * Unknown values fall back to beginner.
     *
     * @param int $difficulty
     * @return $this
     */
    public function setDifficulty(int $difficulty): Exercise
    {
        $this->difficulty = isset(self::DIFFICULTIES[$difficulty]) ? $difficulty : self::DIFFICULTY_BEGINNER;
        return $this;
    }

    /**
     * Returns the translation key of the difficulty.
     *
     * @return string
     */
    public function getDifficultyKey(): string
    {
        return self::DIFFICULTIES[$this->difficulty];
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): Exercise
    {
        $this->image = $image;
        return $this;
    }

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(string $videoUrl): Exercise
    {
        $this->videoUrl = $videoUrl;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): Exercise
    {
        $this->public = $public;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): Exercise
    {
        $this->active = $active;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): Exercise
    {
        $this->position = $position;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): Exercise
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): Exercise
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getMuscleGroupIds(): array
    {
        return $this->muscleGroupIds;
    }

    public function getPrimaryMuscleGroupId(): ?int
    {
        return $this->primaryMuscleGroupId;
    }

    /**
     * Sets the trained muscle groups. The primary muscle group is always part of the list.
     *
     * @param int[] $muscleGroupIds
     * @param int|null $primaryMuscleGroupId 0 or null means no primary muscle group.
     * @return $this
     */
    public function setMuscleGroups(array $muscleGroupIds, ?int $primaryMuscleGroupId = null): Exercise
    {
        $ids = array_map('intval', $muscleGroupIds);
        if ($primaryMuscleGroupId) {
            $ids[] = $primaryMuscleGroupId;
        }

        $this->muscleGroupIds = array_values(array_unique(array_filter($ids)));
        $this->primaryMuscleGroupId = $primaryMuscleGroupId ?: null;
        return $this;
    }

    /**
     * Returns the fields of the exercise table. Muscle groups are stored separately.
     *
     * @param bool $withId
     * @return array
     */
    public function getArray(bool $withId = true): array
    {
        return array_merge(
            ($withId ? ['id' => $this->getId()] : []),
            [
                'category_id' => $this->getCategoryId(),
                'title' => $this->getTitle(),
                'description' => $this->getDescription(),
                'instructions' => $this->getInstructions(),
                'notes' => $this->getNotes(),
                'difficulty' => $this->getDifficulty(),
                'image' => $this->getImage(),
                'video_url' => $this->getVideoUrl(),
                'is_public' => (int)$this->isPublic(),
                'active' => (int)$this->isActive(),
                'position' => $this->getPosition(),
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
            ]
        );
    }
}
