<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;
use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;
use Modules\Fitness\Mappers\Workout as WorkoutMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;
use Modules\Fitness\Service\Access;
use Modules\Fitness\Service\Milestones;
use Modules\Fitness\Service\Progress;

/**
 * The training area of participants. Only for logged-in users, every action checks the access.
 */
class Training extends Base
{
    public function init()
    {
        parent::init();
        $this->requireLogin();
    }

    /**
     * Lists the programs the user takes part in, with progress and next session.
     */
    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($this->getTranslator()->trans('myTraining'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('myTraining'), ['action' => 'index']);

        $orders = (new OrderMapper())->getOrdersOfUser($this->getUser()->getId());

        $this->getView()->set('trainings', $this->getTrainingsOfUser($this->getUser()->getId()))
            ->set('openOrders', array_values(array_filter($orders, static fn ($order) => $order->isOpen())))
            ->set('hasOrders', (bool)$orders);
    }

    /**
     * Shows a session with its workout and all exercises.
     */
    public function sessionAction()
    {
        [$session, $program, $enrollment] = $this->loadSessionWithAccess();

        $structureMapper = new ProgramStructureMapper();
        $phases = $structureMapper->getPhasesOfProgram($program->getId());
        $doneSessions = $enrollment ? (new SessionLogMapper())->getDoneSessions($enrollment->getId()) : [];
        $progress = Progress::calculate($phases, array_keys($doneSessions));

        $workout = (new WorkoutMapper())->getWorkoutById($session->getWorkoutId());
        $exerciseIds = array_map(static fn ($item) => $item->getExerciseId(), $workout->getExercises());
        $exercises = [];
        if ($exerciseIds) {
            foreach ((new ExerciseMapper())->getEntriesBy(['e.id' => array_values(array_unique($exerciseIds))]) as $exercise) {
                $exercises[$exercise->getId()] = $exercise;
            }
        }
        $muscleGroups = [];
        foreach ((new MuscleGroupMapper())->getMuscleGroups() as $muscleGroup) {
            $muscleGroups[$muscleGroup->getId()] = $muscleGroup;
        }

        // Neighbours in the order of the program, for the navigation.
        $orderedSessions = [];
        $phaseOfSession = null;
        $phaseTitles = [];
        foreach ($phases as $phase) {
            $phaseTitles[$phase->getId()] = $phase->getTitle();
            foreach ($phase->getSessions() as $phaseSession) {
                $orderedSessions[] = $phaseSession;
                if ($phaseSession->getId() === $session->getId()) {
                    $phaseOfSession = $phase;
                }
            }
        }
        $index = array_search($session->getId(), array_map(static fn ($item) => $item->getId(), $orderedSessions), true);

        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($program->getTitle())
            ->add($session->getDisplayTitle());
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($program->getTitle(), ['controller' => 'programs', 'action' => 'show', 'id' => $program->getId()])
            ->add($session->getDisplayTitle(), ['action' => 'session', 'id' => $session->getId()]);

        $this->getView()->set('program', $program)
            ->set('session', $session)
            ->set('phase', $phaseOfSession)
            ->set('phaseTitles', $phaseTitles)
            ->set('workout', $workout)
            ->set('exercises', $exercises)
            ->set('muscleGroups', $muscleGroups)
            ->set('enrollment', $enrollment)
            ->set('progress', $progress)
            ->set('doneAt', $doneSessions[$session->getId()] ?? null)
            ->set('reachedMilestones', $this->takeReachedMilestones())
            ->set('previousSession', $index !== false && $index > 0 ? $orderedSessions[$index - 1] : null)
            ->set('nextSession', $index !== false && isset($orderedSessions[$index + 1]) ? $orderedSessions[$index + 1] : null);
    }

    /**
     * Marks a session as done. Only reachable by POST, which Ilch protects with a token.
     */
    public function completeAction()
    {
        [$session, $program, $enrollment] = $this->loadSessionForLogging();

        $logMapper = new SessionLogMapper();
        $logMapper->markDone($enrollment->getId(), $session->getId());

        $progress = Progress::calculate(
            (new ProgramStructureMapper())->getPhasesOfProgram($program->getId()),
            array_keys($logMapper->getDoneSessions($enrollment->getId()))
        );

        $message = 'sessionDone';
        if ($progress->isComplete() && $enrollment->getStatus() !== EnrollmentModel::STATUS_COMPLETED) {
            (new EnrollmentMapper())->updateStatus($enrollment, EnrollmentModel::STATUS_COMPLETED);
            $message = 'programCompleted';
        }

        $userId = $this->getUser()->getId();
        $this->announceMilestones($userId, (new Milestones())->evaluate($userId, $enrollment->getId()));

        $this->redirect()
            ->withMessage($message)
            ->to(['action' => 'session', 'id' => $session->getId()]);
    }

    /**
     * Removes the done mark of a session. Only reachable by POST.
     */
    public function undoAction()
    {
        [$session, $program, $enrollment] = $this->loadSessionForLogging();

        $logMapper = new SessionLogMapper();
        $logMapper->unmarkDone($enrollment->getId(), $session->getId());

        if ($enrollment->getStatus() === EnrollmentModel::STATUS_COMPLETED) {
            $progress = Progress::calculate(
                (new ProgramStructureMapper())->getPhasesOfProgram($program->getId()),
                array_keys($logMapper->getDoneSessions($enrollment->getId()))
            );
            if (!$progress->isComplete()) {
                (new EnrollmentMapper())->updateStatus($enrollment, EnrollmentModel::STATUS_ACTIVE);
            }
        }

        $this->redirect()
            ->withMessage('sessionUndone', 'info')
            ->to(['action' => 'session', 'id' => $session->getId()]);
    }

    /**
     * Loads the session from the request and checks that the user may see it. Managers without
     * enrollment get the session as preview (enrollment null).
     *
     * @return array{ProgramSessionModel, ProgramModel, EnrollmentModel|null}
     */
    private function loadSessionWithAccess(): array
    {
        $session = (new ProgramStructureMapper())->getSessionById((int)$this->getRequest()->getParam('id'));
        $program = $session ? (new ProgramMapper())->getProgramById($session->getProgramId()) : null;

        if (!$session || !$program) {
            $this->redirect()
                ->withMessage('sessionNotFound', 'warning')
                ->to(['action' => 'index']);
        }

        $access = new Access();
        $enrollment = $access->getAccessEnrollment($this->getUser(), $program);

        if (!$enrollment && !Access::canManage($this->getUser())) {
            $this->redirect()
                ->withMessage('noAccessToProgram', 'warning')
                ->to(['controller' => 'programs', 'action' => 'show', 'id' => $program->getId()]);
        }

        return [$session, $program, $enrollment];
    }

    /**
     * Like loadSessionWithAccess(), but requires a POST request and an enrollment that may log
     * sessions. Previews of managers can't log anything.
     *
     * @return array{ProgramSessionModel, ProgramModel, EnrollmentModel}
     */
    private function loadSessionForLogging(): array
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect(['action' => 'session', 'id' => (int)$this->getRequest()->getParam('id')]);
        }

        [$session, $program, $enrollment] = $this->loadSessionWithAccess();

        if (!$enrollment || !$enrollment->canLogSessions()) {
            $this->redirect()
                ->withMessage('noAccessToProgram', 'warning')
                ->to(['action' => 'session', 'id' => $session->getId()]);
        }

        return [$session, $program, $enrollment];
    }
}
