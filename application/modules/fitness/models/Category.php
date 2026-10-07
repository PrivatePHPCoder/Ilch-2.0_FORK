<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

class Category extends Model
{
    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * @var string
     */
    protected string $name = '';

    /**
     * @var int
     */
    protected int $position = 0;

    /**
     * Number of exercises in this category. Only filled when loaded by the mapper.
     *
     * @var int
     */
    protected int $exerciseCount = 0;

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Category
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (isset($entries['name'])) {
            $this->setName($entries['name']);
        }
        if (isset($entries['position'])) {
            $this->setPosition($entries['position']);
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

    public function setId(int $id): Category
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Category
    {
        $this->name = $name;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): Category
    {
        $this->position = $position;
        return $this;
    }

    public function getExerciseCount(): int
    {
        return $this->exerciseCount;
    }

    public function setExerciseCount(int $exerciseCount): Category
    {
        $this->exerciseCount = $exerciseCount;
        return $this;
    }

    /**
     * @param bool $withId
     * @return array
     */
    public function getArray(bool $withId = true): array
    {
        return array_merge(
            ($withId ? ['id' => $this->getId()] : []),
            [
                'name' => $this->getName(),
                'position' => $this->getPosition(),
            ]
        );
    }
}
