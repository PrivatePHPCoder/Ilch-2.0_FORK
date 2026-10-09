<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Models\Enrollment;
use Modules\Fitness\Models\Program;
use Modules\User\Models\User;

/**
 * Decides on the server who may see the content of a program.
 *
 * Every action that shows or changes program content asks this class.
 */
class Access
{
    /**
     * @var EnrollmentMapper
     */
    private EnrollmentMapper $enrollmentMapper;

    /**
     * Whether managers may preview all content. Off while an admin views the area as a visitor.
     *
     * @var bool
     */
    private bool $previewEnabled;

    public function __construct(?EnrollmentMapper $enrollmentMapper = null, bool $previewEnabled = true)
    {
        $this->enrollmentMapper = $enrollmentMapper ?? new EnrollmentMapper();
        $this->previewEnabled = $previewEnabled;
    }

    /**
     * Whether the user may manage the fitness module (admins and groups with admin rights on it).
     * They may preview everything.
     *
     * @param User|null $user
     * @return bool
     */
    public static function canManage(?User $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->hasAccess('module_fitness'));
    }

    /**
     * Whether the user sees content because of manager rights, without taking part.
     *
     * @param User|null $user
     * @return bool
     */
    public function canPreview(?User $user): bool
    {
        return $this->previewEnabled && self::canManage($user);
    }

    /**
     * Returns the enrollment that gives the user access to the program content.
     *
     * The enrollment alone decides: participants keep their access even if the program is archived
     * later or its group visibility changes. Paused and revoked enrollments give no access.
     *
     * @param User|null $user
     * @param Program $program
     * @return Enrollment|null
     */
    public function getAccessEnrollment(?User $user, Program $program): ?Enrollment
    {
        if ($user === null) {
            return null;
        }

        $enrollment = $this->enrollmentMapper->getEnrollment($program->getId(), $user->getId());

        return $enrollment && $enrollment->grantsAccess() ? $enrollment : null;
    }

    /**
     * Whether the user may see workouts, exercises and files of the program.
     *
     * @param User|null $user
     * @param Program $program
     * @return bool
     */
    public function canViewProgramContent(?User $user, Program $program): bool
    {
        return $this->canPreview($user) || $this->getAccessEnrollment($user, $program) !== null;
    }

    /**
     * Whether the user may start a free program right now.
     *
     * @param User|null $user
     * @param Program $program
     * @param int[] $groupIds groups of the user
     * @return bool
     */
    public function canJoinForFree(?User $user, Program $program, array $groupIds): bool
    {
        return $user !== null
            && $program->isPublished()
            && !$program->isPaid()
            && $program->isVisibleForGroups($groupIds);
    }

    /**
     * Whether the user may order a paid program. Access comes only after the payment is confirmed.
     *
     * @param User|null $user
     * @param Program $program
     * @param int[] $groupIds groups of the user
     * @return bool
     */
    public function canBuy(?User $user, Program $program, array $groupIds): bool
    {
        return $user !== null
            && $program->isPublished()
            && $program->isPaid()
            && $program->isVisibleForGroups($groupIds);
    }
}
