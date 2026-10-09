<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;

class Index extends Base
{
    /**
     * Number of programs shown on the dashboard.
     */
    private const FEATURED_PROGRAMS = 3;

    /**
     * Number of reached milestones shown on the dashboard.
     */
    private const LATEST_MILESTONES = 4;

    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['action' => 'index']);

        $programs = (new ProgramMapper())->getPublishedProgramsForGroups($this->getVisitorGroupIds());
        $publicExercises = (new ExerciseMapper())->getEntriesBy(['e.active' => 1, 'e.is_public' => 1]);

        $trainings = [];
        $overview = null;
        $reached = [];
        if ($this->getUser()) {
            $trainings = $this->getTrainingsOfUser($this->getUser()->getId());
            $overview = $this->getMilestoneOverview($this->getUser()->getId(), $trainings);

            $achievements = $overview['achievements'];
            $reached = array_filter($overview['milestones'], static fn ($milestone) => isset($achievements[$milestone->getId()]));
            usort($reached, static fn ($a, $b) => strcmp($achievements[$b->getId()], $achievements[$a->getId()]));
        }

        $this->getView()->set('programs', array_slice($programs, 0, self::FEATURED_PROGRAMS))
            ->set('programCount', count($programs))
            ->set('exerciseCount', count($publicExercises))
            ->set('user', $this->getUser())
            ->set('trainings', $trainings)
            ->set('overview', $overview)
            ->set('reachedCount', count($reached))
            ->set('latestMilestones', array_slice($reached, 0, self::LATEST_MILESTONES));
    }
}
