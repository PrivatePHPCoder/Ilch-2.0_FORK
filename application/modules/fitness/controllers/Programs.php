<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;

class Programs extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($this->getTranslator()->trans('menuPrograms'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuPrograms'), ['action' => 'index']);

        $this->getView()->set('programs', (new ProgramMapper())->getPublishedProgramsForGroups($this->getVisitorGroupIds()));
    }

    /**
     * Shows the description and the structure of a program. The workouts themselves are only
     * shown to participants, which comes with the enrollment.
     */
    public function showAction()
    {
        $program = (new ProgramMapper())->getProgramById((int)$this->getRequest()->getParam('id'));
        $isPreview = false;

        if (!$program || !$program->isPublished() || !$program->isVisibleForGroups($this->getVisitorGroupIds())) {
            if (!$program || !$this->canManageFitness()) {
                $this->redirect()
                    ->withMessage('programNotFound', 'warning')
                    ->to(['action' => 'index']);
            }
            $isPreview = true;
        }

        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($program->getTitle());
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuPrograms'), ['action' => 'index'])
            ->add($program->getTitle(), ['action' => 'show', 'id' => $program->getId()]);

        $this->getView()->set('program', $program)
            ->set('phases', (new ProgramStructureMapper())->getPhasesOfProgram($program->getId()))
            ->set('isPreview', $isPreview);
    }
}
