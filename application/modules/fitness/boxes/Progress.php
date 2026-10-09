<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Boxes;

use Modules\Fitness\Mappers\Milestone as MilestoneMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Service\Trainings;

/**
 * Box with the active programs of the logged-in user and the next session.
 */
class Progress extends \Ilch\Box
{
    /**
     * Number of programs shown in the box.
     */
    private const MAX_TRAININGS = 3;

    public function render()
    {
        $user = $this->getUser();
        $trainings = [];
        $milestoneCount = 0;

        if ($user) {
            $trainings = array_filter(
                (new Trainings())->getOfUser($user->getId()),
                static fn ($training) => $training['enrollment']->getStatus() === EnrollmentModel::STATUS_ACTIVE
            );
            $milestoneCount = count((new MilestoneMapper())->getAchievementsOfUser($user->getId()));
        }

        $this->getView()->set('user', $user)
            ->set('trainings', array_slice($trainings, 0, self::MAX_TRAININGS))
            ->set('milestoneCount', $milestoneCount);
    }
}
