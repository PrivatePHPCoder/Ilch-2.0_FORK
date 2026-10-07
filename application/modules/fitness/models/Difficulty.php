<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

/**
 * Difficulty levels shared by exercises, workouts and programs.
 */
final class Difficulty
{
    public const BEGINNER = 1;
    public const INTERMEDIATE = 2;
    public const ADVANCED = 3;

    /**
     * Translation keys of the difficulty levels.
     *
     * @var array<int, string>
     */
    public const KEYS = [
        self::BEGINNER => 'difficultyBeginner',
        self::INTERMEDIATE => 'difficultyIntermediate',
        self::ADVANCED => 'difficultyAdvanced',
    ];

    /**
     * Returns the given level or beginner, if the level is unknown.
     *
     * @param int $difficulty
     * @return int
     */
    public static function normalize(int $difficulty): int
    {
        return isset(self::KEYS[$difficulty]) ? $difficulty : self::BEGINNER;
    }

    /**
     * Returns the translation key of a level.
     *
     * @param int $difficulty
     * @return string
     */
    public static function getKey(int $difficulty): string
    {
        return self::KEYS[self::normalize($difficulty)];
    }
}
