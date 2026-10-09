<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;
use Ilch\Translator;

/**
 * A goal participants can reach, for example "5 sessions done" or "first week done".
 *
 * Without a program the milestone counts across all programs of a user, with a program
 * only inside that program. A new kind of milestone only needs a new type and its
 * calculation in Service\Milestones, the table stays the same.
 */
class Milestone extends Model
{
    public const TYPE_SESSIONS = 'sessions_completed';
    public const TYPE_PHASES = 'phases_completed';
    public const TYPE_PERCENT = 'program_percent';
    public const TYPE_PROGRAMS = 'programs_completed';

    /**
     * Translation keys of the types.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        self::TYPE_SESSIONS => 'milestoneTypeSessions',
        self::TYPE_PHASES => 'milestoneTypePhases',
        self::TYPE_PERCENT => 'milestoneTypePercent',
        self::TYPE_PROGRAMS => 'milestoneTypePrograms',
    ];

    /**
     * Highest allowed threshold per type.
     *
     * @var array<string, int>
     */
    public const MAX_THRESHOLDS = [
        self::TYPE_SESSIONS => 10000,
        self::TYPE_PHASES => 1000,
        self::TYPE_PERCENT => 100,
        self::TYPE_PROGRAMS => 1000,
    ];

    /**
     * Icons that can be chosen in the admin area.
     *
     * @var string[]
     */
    public const ICONS = [
        'fa-solid fa-medal',
        'fa-solid fa-trophy',
        'fa-solid fa-star',
        'fa-solid fa-award',
        'fa-solid fa-fire',
        'fa-solid fa-bolt',
        'fa-solid fa-flag-checkered',
        'fa-solid fa-mountain',
        'fa-solid fa-dumbbell',
        'fa-solid fa-heart-pulse',
        'fa-solid fa-crown',
        'fa-solid fa-gem',
    ];

    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var int|null
     */
    protected ?int $programId = null;

    /**
     * Title of the program. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $programTitle = '';

    /**
     * @var string
     */
    protected string $type = self::TYPE_SESSIONS;

    /**
     * @var int
     */
    protected int $threshold = 1;

    /**
     * Own title. Without one, a title is built from the type and threshold.
     *
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var string
     */
    protected string $icon = self::ICONS[0];

    /**
     * @var int
     */
    protected int $position = 0;

    /**
     * @var bool
     */
    protected bool $active = true;

    /**
     * Number of users who reached it. Only filled when loaded by the mapper.
     *
     * @var int
     */
    protected int $achievedCount = 0;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Milestone
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (array_key_exists('program_id', $entries)) {
            $this->setProgramId(WorkoutExercise::toNullableInt($entries['program_id']));
        }
        if (isset($entries['program_title'])) {
            $this->setProgramTitle($entries['program_title']);
        }
        if (isset($entries['type'])) {
            $this->setType($entries['type']);
        }
        if (isset($entries['threshold'])) {
            $this->setThreshold($entries['threshold']);
        }
        if (isset($entries['title'])) {
            $this->setTitle($entries['title']);
        }
        if (isset($entries['description'])) {
            $this->setDescription($entries['description']);
        }
        if (isset($entries['icon'])) {
            $this->setIcon($entries['icon']);
        }
        if (isset($entries['position'])) {
            $this->setPosition($entries['position']);
        }
        if (isset($entries['active'])) {
            $this->setActive($entries['active']);
        }
        if (isset($entries['achieved_count'])) {
            $this->setAchievedCount($entries['achieved_count']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Milestone
    {
        $this->id = $id;
        return $this;
    }

    public function getProgramId(): ?int
    {
        return $this->programId;
    }

    /**
     * @param int|null $programId null or 0 means: counts across all programs
     * @return $this
     */
    public function setProgramId(?int $programId): Milestone
    {
        $this->programId = $programId ?: null;
        return $this;
    }

    public function isForAllPrograms(): bool
    {
        return $this->programId === null;
    }

    public function getProgramTitle(): string
    {
        return $this->programTitle;
    }

    public function setProgramTitle(string $programTitle): Milestone
    {
        $this->programTitle = $programTitle;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type one of the TYPE_* constants, everything else becomes TYPE_SESSIONS
     * @return $this
     */
    public function setType(string $type): Milestone
    {
        $this->type = isset(self::TYPES[$type]) ? $type : self::TYPE_SESSIONS;
        return $this;
    }

    public function getTypeKey(): string
    {
        return self::TYPES[$this->type];
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): Milestone
    {
        $this->threshold = $threshold;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Milestone
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns the own title or one built from type and threshold.
     *
     * @param Translator $translator
     * @return string
     */
    public function getDisplayTitle(Translator $translator): string
    {
        if ($this->title !== '') {
            return $this->title;
        }

        return $translator->trans($this->getAutoTitleKey(), $this->threshold);
    }

    /**
     * Translation key of the title that is used when no own title is set.
     *
     * @return string
     */
    public function getAutoTitleKey(): string
    {
        $first = $this->threshold === 1;

        switch ($this->type) {
            case self::TYPE_PHASES:
                return $first ? 'milestoneAutoFirstPhase' : 'milestoneAutoPhases';
            case self::TYPE_PERCENT:
                return $this->threshold === 50 ? 'milestoneAutoHalfway' : 'milestoneAutoPercent';
            case self::TYPE_PROGRAMS:
                if (!$this->isForAllPrograms()) {
                    return 'milestoneAutoProgramDone';
                }
                return $first ? 'milestoneAutoFirstProgram' : 'milestoneAutoPrograms';
            default:
                return $first ? 'milestoneAutoFirstSession' : 'milestoneAutoSessions';
        }
    }

    /**
     * Translation key of the sentence that explains how to reach the milestone.
     *
     * @return string
     */
    public function getConditionKey(): string
    {
        $first = $this->threshold === 1;

        switch ($this->type) {
            case self::TYPE_PHASES:
                return $first ? 'milestoneConditionFirstPhase' : 'milestoneConditionPhases';
            case self::TYPE_PERCENT:
                return $this->isForAllPrograms() ? 'milestoneConditionPercent' : 'milestoneConditionPercentProgram';
            case self::TYPE_PROGRAMS:
                if (!$this->isForAllPrograms()) {
                    return 'milestoneConditionProgramDone';
                }
                return $first ? 'milestoneConditionFirstProgram' : 'milestoneConditionPrograms';
            default:
                return $first ? 'milestoneConditionFirstSession' : 'milestoneConditionSessions';
        }
    }

    /**
     * Translation key for "x of y" in the unit of the type, for example "3 of 5 sessions".
     *
     * @return string
     */
    public function getProgressKey(): string
    {
        switch ($this->type) {
            case self::TYPE_PHASES:
                return 'milestoneProgressPhases';
            case self::TYPE_PERCENT:
                return 'milestoneProgressPercent';
            case self::TYPE_PROGRAMS:
                return 'milestoneProgressPrograms';
            default:
                return 'milestoneProgressSessions';
        }
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Milestone
    {
        $this->description = $description;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon one of ICONS, everything else becomes the first icon
     * @return $this
     */
    public function setIcon(string $icon): Milestone
    {
        $this->icon = in_array($icon, self::ICONS, true) ? $icon : self::ICONS[0];
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): Milestone
    {
        $this->position = $position;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): Milestone
    {
        $this->active = $active;
        return $this;
    }

    public function getAchievedCount(): int
    {
        return $this->achievedCount;
    }

    public function setAchievedCount(int $achievedCount): Milestone
    {
        $this->achievedCount = $achievedCount;
        return $this;
    }

    /**
     * Returns the fields of the milestones table.
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
                'type' => $this->getType(),
                'threshold' => $this->getThreshold(),
                'title' => $this->getTitle(),
                'description' => $this->getDescription(),
                'icon' => $this->getIcon(),
                'position' => $this->getPosition(),
                'active' => (int)$this->isActive(),
            ]
        );
    }
}
