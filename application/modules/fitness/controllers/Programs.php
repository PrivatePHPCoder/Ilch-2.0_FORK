<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;
use Modules\Fitness\Service\Access;
use Modules\Fitness\Service\Progress;

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
     * Shows the description and the structure of a program. Participants also see their progress
     * and can open the sessions.
     */
    public function showAction()
    {
        $program = (new ProgramMapper())->getProgramById((int)$this->getRequest()->getParam('id'));
        $user = $this->getUser();
        $enrollment = $program && $user ? (new EnrollmentMapper())->getEnrollment($program->getId(), $user->getId()) : null;
        $isOffered = $program && $program->isPublished() && $program->isVisibleForGroups($this->getVisitorGroupIds());
        $isPreview = false;

        if (!$program || (!$isOffered && !$enrollment)) {
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

        $phases = (new ProgramStructureMapper())->getPhasesOfProgram($program->getId());
        $access = new Access();
        $canViewContent = $access->canViewProgramContent($user, $program);
        $progress = null;
        if ($enrollment) {
            $progress = Progress::calculate($phases, array_keys((new SessionLogMapper())->getDoneSessions($enrollment->getId())));
        }

        $this->getView()->set('program', $program)
            ->set('phases', $phases)
            ->set('isPreview', $isPreview)
            ->set('enrollment', $enrollment)
            ->set('progress', $progress)
            ->set('canViewContent', $canViewContent)
            ->set('canJoin', !$enrollment && $access->canJoinForFree($user, $program, $this->getVisitorGroupIds()));
    }

    /**
     * Lets the current user take part in a free program. Only reachable by POST, which Ilch
     * protects with a token.
     */
    public function joinAction()
    {
        $programId = (int)$this->getRequest()->getParam('id');

        if (!$this->getRequest()->isPost()) {
            $this->redirect(['action' => 'show', 'id' => $programId]);
        }

        $this->requireLogin();

        $program = (new ProgramMapper())->getProgramById($programId);
        if (!$program) {
            $this->redirect()
                ->withMessage('programNotFound', 'warning')
                ->to(['action' => 'index']);
        }

        $access = new Access();
        $enrollmentMapper = new EnrollmentMapper();

        if ($enrollmentMapper->getEnrollment($program->getId(), $this->getUser()->getId())) {
            $this->redirect()
                ->withMessage('alreadyJoined', 'info')
                ->to(['action' => 'show', 'id' => $program->getId()]);
        }

        if ($program->isPaid()) {
            $this->redirect()
                ->withMessage('paidComingSoon', 'info')
                ->to(['action' => 'show', 'id' => $program->getId()]);
        }

        if (!$access->canJoinForFree($this->getUser(), $program, $this->getVisitorGroupIds())) {
            $this->redirect()
                ->withMessage('programNotFound', 'warning')
                ->to(['action' => 'index']);
        }

        $enrollment = $enrollmentMapper->enroll($program->getId(), $this->getUser()->getId());
        if (!$enrollment) {
            $this->redirect()
                ->withMessage('joinFailed', 'danger')
                ->to(['action' => 'show', 'id' => $program->getId()]);
        }

        $progress = Progress::calculate((new ProgramStructureMapper())->getPhasesOfProgram($program->getId()), []);
        $nextSession = $progress->getNextSession();

        $this->redirect()
            ->withMessage('joinSuccess')
            ->to($nextSession
                ? ['controller' => 'training', 'action' => 'session', 'id' => $nextSession->getId()]
                : ['action' => 'show', 'id' => $program->getId()]);
    }
}
