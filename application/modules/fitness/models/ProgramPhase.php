<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * A part of a program, usually a week.
 */
class ProgramPhase extends Model
{
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
    protected int $position = 0;

    /**
     * For example "Woche 1".
     *
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * Sessions of this phase in their order.
     *
     * @var ProgramSession[]
     */
    protected array $sessions = [];

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): ProgramPhase
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['program_id'])) {
            $this->setProgramId($entries['program_id']);
        }
        if (isset($entries['position'])) {
            $this->setPosition($entries['position']);
        }
        if (isset($entries['title'])) {
            $this->setTitle($entries['title']);
        }
        if (isset($entries['description'])) {
            $this->setDescription($entries['description']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ProgramPhase
    {
        $this->id = $id;
        return $this;
    }

    public function getProgramId(): int
    {
        return $this->programId;
    }

    public function setProgramId(int $programId): ProgramPhase
    {
        $this->programId = $programId;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): ProgramPhase
    {
        $this->position = $position;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ProgramPhase
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): ProgramPhase
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ProgramSession[]
     */
    public function getSessions(): array
    {
        return $this->sessions;
    }

    /**
     * @param ProgramSession[] $sessions in their order
     * @return $this
     */
    public function setSessions(array $sessions): ProgramPhase
    {
        $this->sessions = array_values($sessions);
        return $this;
    }

    /**
     * Returns the fields of the program_phases table.
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
                'position' => $this->getPosition(),
                'title' => $this->getTitle(),
                'description' => $this->getDescription(),
            ]
        );
    }
}
