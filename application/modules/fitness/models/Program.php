<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * A training program made of phases (for example weeks) with training sessions.
 */
class Program extends Model
{
    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_ARCHIVED = 2;

    /**
     * Translation keys of the states.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT => 'statusDraft',
        self::STATUS_PUBLISHED => 'statusPublished',
        self::STATUS_ARCHIVED => 'statusArchived',
    ];

    public const ACCESS_FREE = 0;
    public const ACCESS_PAID = 1;

    /**
     * Program types. Only weekly programs exist so far, the column allows more later.
     *
     * @var string[]
     */
    public const TYPES = ['weekly'];

    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var string
     */
    protected string $title = '';

    /**
     * Short text for lists.
     *
     * @var string
     */
    protected string $teaser = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var string
     */
    protected string $image = '';

    /**
     * For example "Muskelaufbau" or "Abnehmen".
     *
     * @var string
     */
    protected string $goal = '';

    /**
     * @var int
     */
    protected int $difficulty = Difficulty::BEGINNER;

    /**
     * @var string
     */
    protected string $type = 'weekly';

    /**
     * @var int
     */
    protected int $accessType = self::ACCESS_FREE;

    /**
     * Price as decimal string like "29.90", so no float rounding happens.
     *
     * @var string
     */
    protected string $price = '0.00';

    /**
     * @var string
     */
    protected string $currency = 'EUR';

    /**
     * @var int
     */
    protected int $status = self::STATUS_DRAFT;

    /**
     * Visible for all groups. Otherwise only for the groups in $groupIds.
     *
     * @var bool
     */
    protected bool $readAccessAll = true;

    /**
     * @var int[]
     */
    protected array $groupIds = [];

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
     * Number of phases. Only filled when loaded by the mapper.
     *
     * @var int
     */
    protected int $phaseCount = 0;

    /**
     * Number of sessions. Only filled when loaded by the mapper.
     *
     * @var int
     */
    protected int $sessionCount = 0;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Program
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['title'])) {
            $this->setTitle($entries['title']);
        }
        if (isset($entries['teaser'])) {
            $this->setTeaser($entries['teaser']);
        }
        if (isset($entries['description'])) {
            $this->setDescription($entries['description']);
        }
        if (isset($entries['image'])) {
            $this->setImage($entries['image']);
        }
        if (isset($entries['goal'])) {
            $this->setGoal($entries['goal']);
        }
        if (isset($entries['difficulty'])) {
            $this->setDifficulty($entries['difficulty']);
        }
        if (isset($entries['type'])) {
            $this->setType($entries['type']);
        }
        if (isset($entries['access_type'])) {
            $this->setAccessType($entries['access_type']);
        }
        if (isset($entries['price'])) {
            $this->setPrice((string)$entries['price']);
        }
        if (isset($entries['currency'])) {
            $this->setCurrency($entries['currency']);
        }
        if (isset($entries['status'])) {
            $this->setStatus($entries['status']);
        }
        if (isset($entries['read_access_all'])) {
            $this->setReadAccessAll($entries['read_access_all']);
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

    public function setId(int $id): Program
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Program
    {
        $this->title = $title;
        return $this;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function setTeaser(string $teaser): Program
    {
        $this->teaser = $teaser;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Program
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): Program
    {
        $this->image = $image;
        return $this;
    }

    public function getGoal(): string
    {
        return $this->goal;
    }

    public function setGoal(string $goal): Program
    {
        $this->goal = $goal;
        return $this;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): Program
    {
        $this->difficulty = Difficulty::normalize($difficulty);
        return $this;
    }

    public function getDifficultyKey(): string
    {
        return Difficulty::getKey($this->difficulty);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Unknown types fall back to "weekly".
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): Program
    {
        $this->type = in_array($type, self::TYPES, true) ? $type : 'weekly';
        return $this;
    }

    public function getAccessType(): int
    {
        return $this->accessType;
    }

    public function setAccessType(int $accessType): Program
    {
        $this->accessType = $accessType === self::ACCESS_PAID ? self::ACCESS_PAID : self::ACCESS_FREE;
        return $this;
    }

    public function isPaid(): bool
    {
        return $this->accessType === self::ACCESS_PAID;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * Stores the price with two decimals. Accepts "29,9" as well as "29.90".
     *
     * @param string $price
     * @return $this
     */
    public function setPrice(string $price): Program
    {
        $price = str_replace(',', '.', trim($price));
        $this->price = is_numeric($price) && (float)$price >= 0 ? number_format((float)$price, 2, '.', '') : '0.00';
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): Program
    {
        $currency = strtoupper(trim($currency));
        $this->currency = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'EUR';
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): Program
    {
        $this->status = isset(self::STATUSES[$status]) ? $status : self::STATUS_DRAFT;
        return $this;
    }

    public function getStatusKey(): string
    {
        return self::STATUSES[$this->status];
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isReadAccessAll(): bool
    {
        return $this->readAccessAll;
    }

    public function setReadAccessAll(bool $readAccessAll): Program
    {
        $this->readAccessAll = $readAccessAll;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getGroupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * @param int[] $groupIds
     * @return $this
     */
    public function setGroupIds(array $groupIds): Program
    {
        $this->groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
        return $this;
    }

    /**
     * Returns whether one of the given groups may see the program.
     *
     * @param int[] $groupIds
     * @return bool
     */
    public function isVisibleForGroups(array $groupIds): bool
    {
        return $this->readAccessAll || array_intersect($this->groupIds, array_map('intval', $groupIds));
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): Program
    {
        $this->position = $position;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): Program
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): Program
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPhaseCount(): int
    {
        return $this->phaseCount;
    }

    public function setPhaseCount(int $phaseCount): Program
    {
        $this->phaseCount = $phaseCount;
        return $this;
    }

    public function getSessionCount(): int
    {
        return $this->sessionCount;
    }

    public function setSessionCount(int $sessionCount): Program
    {
        $this->sessionCount = $sessionCount;
        return $this;
    }

    /**
     * Returns the fields of the programs table. Group access is stored separately.
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
                'teaser' => $this->getTeaser(),
                'description' => $this->getDescription(),
                'image' => $this->getImage(),
                'goal' => $this->getGoal(),
                'difficulty' => $this->getDifficulty(),
                'type' => $this->getType(),
                'access_type' => $this->getAccessType(),
                'price' => $this->getPrice(),
                'currency' => $this->getCurrency(),
                'status' => $this->getStatus(),
                'read_access_all' => (int)$this->isReadAccessAll(),
                'position' => $this->getPosition(),
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
            ]
        );
    }
}
