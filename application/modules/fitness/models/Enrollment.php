<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * A user taking part in a program.
 */
class Enrollment extends Model
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_PAUSED = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_REVOKED = 4;

    /**
     * Translation keys of the states.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_ACTIVE => 'enrollmentActive',
        self::STATUS_PAUSED => 'enrollmentPaused',
        self::STATUS_COMPLETED => 'enrollmentCompleted',
        self::STATUS_REVOKED => 'enrollmentRevoked',
    ];

    public const SOURCE_FREE = 0;
    public const SOURCE_ORDER = 1;
    public const SOURCE_MANUAL = 2;

    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var int
     */
    protected int $programId = 0;

    /**
     * Title of the program. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $programTitle = '';

    /**
     * @var int
     */
    protected int $userId = 0;

    /**
     * Name of the user. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $userName = '';

    /**
     * @var int
     */
    protected int $status = self::STATUS_ACTIVE;

    /**
     * @var int
     */
    protected int $source = self::SOURCE_FREE;

    /**
     * @var int|null
     */
    protected ?int $orderId = null;

    /**
     * @var string
     */
    protected string $startedAt = '';

    /**
     * @var string|null
     */
    protected ?string $completedAt = null;

    /**
     * @var string|null
     */
    protected ?string $accessUntil = null;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Enrollment
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['program_id'])) {
            $this->setProgramId($entries['program_id']);
        }
        if (isset($entries['program_title'])) {
            $this->setProgramTitle($entries['program_title']);
        }
        if (isset($entries['user_id'])) {
            $this->setUserId($entries['user_id']);
        }
        if (isset($entries['user_name'])) {
            $this->setUserName($entries['user_name']);
        }
        if (isset($entries['status'])) {
            $this->setStatus($entries['status']);
        }
        if (isset($entries['source'])) {
            $this->setSource($entries['source']);
        }
        if (array_key_exists('order_id', $entries)) {
            $this->setOrderId(WorkoutExercise::toNullableInt($entries['order_id']));
        }
        if (isset($entries['started_at'])) {
            $this->setStartedAt($entries['started_at']);
        }
        if (array_key_exists('completed_at', $entries)) {
            $this->setCompletedAt($entries['completed_at']);
        }
        if (array_key_exists('access_until', $entries)) {
            $this->setAccessUntil($entries['access_until']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Enrollment
    {
        $this->id = $id;
        return $this;
    }

    public function getProgramId(): int
    {
        return $this->programId;
    }

    public function setProgramId(int $programId): Enrollment
    {
        $this->programId = $programId;
        return $this;
    }

    public function getProgramTitle(): string
    {
        return $this->programTitle;
    }

    public function setProgramTitle(string $programTitle): Enrollment
    {
        $this->programTitle = $programTitle;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): Enrollment
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): Enrollment
    {
        $this->userName = $userName;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): Enrollment
    {
        $this->status = isset(self::STATUSES[$status]) ? $status : self::STATUS_ACTIVE;
        return $this;
    }

    public function getStatusKey(): string
    {
        return self::STATUSES[$this->status];
    }

    /**
     * Whether the participant may open the training content. Paused and revoked
     * participations don't give access.
     *
     * @return bool
     */
    public function grantsAccess(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_COMPLETED], true);
    }

    /**
     * Whether new sessions may be logged.
     *
     * @return bool
     */
    public function canLogSessions(): bool
    {
        return $this->grantsAccess();
    }

    public function getSource(): int
    {
        return $this->source;
    }

    public function setSource(int $source): Enrollment
    {
        $this->source = in_array($source, [self::SOURCE_FREE, self::SOURCE_ORDER, self::SOURCE_MANUAL], true) ? $source : self::SOURCE_FREE;
        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): Enrollment
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getStartedAt(): string
    {
        return $this->startedAt;
    }

    public function setStartedAt(string $startedAt): Enrollment
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?string $completedAt): Enrollment
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getAccessUntil(): ?string
    {
        return $this->accessUntil;
    }

    public function setAccessUntil(?string $accessUntil): Enrollment
    {
        $this->accessUntil = $accessUntil;
        return $this;
    }

    /**
     * Returns the fields of the enrollments table.
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
                'user_id' => $this->getUserId(),
                'status' => $this->getStatus(),
                'source' => $this->getSource(),
                'order_id' => $this->getOrderId(),
                'started_at' => $this->getStartedAt(),
                'completed_at' => $this->getCompletedAt(),
                'access_until' => $this->getAccessUntil(),
            ]
        );
    }
}
