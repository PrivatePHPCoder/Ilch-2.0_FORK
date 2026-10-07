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

    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['action' => 'index']);

        $programs = (new ProgramMapper())->getPublishedProgramsForGroups($this->getVisitorGroupIds());
        $publicExercises = (new ExerciseMapper())->getEntriesBy(['e.active' => 1, 'e.is_public' => 1]);

        $this->getView()->set('programs', array_slice($programs, 0, self::FEATURED_PROGRAMS))
            ->set('programCount', count($programs))
            ->set('exerciseCount', count($publicExercises))
            ->set('user', $this->getUser());
    }
}
