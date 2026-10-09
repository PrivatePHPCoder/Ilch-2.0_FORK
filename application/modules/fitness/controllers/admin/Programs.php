<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Ilch\Validation\ErrorBag;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\Workout as WorkoutMapper;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Models\ProgramPhase as ProgramPhaseModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;
use Modules\Fitness\Models\WorkoutExercise as WorkoutExerciseModel;
use Modules\User\Mappers\Group as GroupMapper;

class Programs extends Base
{
    /**
     * Maximum number of weeks the week plan generator creates at once.
     */
    private const MAX_GENERATED_WEEKS = 52;

    public function indexAction()
    {
        $programMapper = new ProgramMapper();

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuPrograms'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') === 'delete' && $this->getRequest()->getPost('check_programs')) {
            $notDeleted = 0;
            foreach ($this->getRequest()->getPost('check_programs') as $id) {
                if (!$programMapper->delete((int)$id)) {
                    $notDeleted++;
                }
            }

            $this->redirect()
                ->withMessage($notDeleted ? 'programsInUse' : 'deleteSuccess', $notDeleted ? 'warning' : 'success')
                ->to(['action' => 'index']);
        }

        if ($this->getRequest()->getPost('saveOrder')) {
            $programMapper->updatePositions((array)$this->getRequest()->getPost('items'));

            $this->redirect()
                ->withMessage('saveSuccess')
                ->to(['action' => 'index']);
        }

        $this->getView()->set('programs', $programMapper->getPrograms())
            ->set('participantCounts', (new EnrollmentMapper())->getCountsPerProgram());
    }

    public function treatAction()
    {
        $programMapper = new ProgramMapper();
        $program = new ProgramModel();

        if ($this->getRequest()->getParam('id')) {
            $program = $programMapper->getProgramById((int)$this->getRequest()->getParam('id'));

            if (!$program) {
                $this->redirect()
                    ->withMessage('entryNotFound', 'danger')
                    ->to(['action' => 'index']);
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuPrograms'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($program->getId() ? 'edit' : 'add'), array_merge(['action' => 'treat'], $program->getId() ? ['id' => $program->getId()] : []));

        $groups = array_filter((new GroupMapper())->getGroupList() ?? [], static fn ($group) => $group->getId() !== 1);

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'title' => 'required|max:255,string',
                'teaser' => 'max:500,string',
                'goal' => 'max:255,string',
                'image' => 'max:255,string',
                'difficulty' => 'required|integer|min:1|max:3',
                'status' => 'required|integer|min:0|max:2',
                'accessType' => 'required|integer|min:0|max:1',
                'groups' => 'required',
            ]);

            $image = trim((string)$this->getRequest()->getPost('image'));
            if (!$this->isValidImage($image)) {
                $validation->getErrorBag()->addError('image', $this->getTranslator()->trans('invalidImage'));
            }

            $accessType = (int)$this->getRequest()->getPost('accessType');
            $price = str_replace(',', '.', trim((string)$this->getRequest()->getPost('price')));
            if ($accessType === ProgramModel::ACCESS_PAID && (!is_numeric($price) || (float)$price <= 0 || (float)$price > 99999)) {
                $validation->getErrorBag()->addError('price', $this->getTranslator()->trans('invalidPrice'));
            }

            $currency = strtoupper(trim((string)$this->getRequest()->getPost('currency')));
            if ($accessType === ProgramModel::ACCESS_PAID && !preg_match('/^[A-Z]{3}$/', $currency)) {
                $validation->getErrorBag()->addError('currency', $this->getTranslator()->trans('invalidCurrency'));
            }

            if ($validation->isValid()) {
                $selectedGroups = (array)$this->getRequest()->getPost('groups');
                $groupIds = array_map(static fn ($group) => $group->getId(), $groups);

                $program->setTitle(trim($this->getRequest()->getPost('title')))
                    ->setTeaser(trim((string)$this->getRequest()->getPost('teaser')))
                    ->setDescription((string)$this->getRequest()->getPost('description'))
                    ->setImage($image)
                    ->setGoal(trim((string)$this->getRequest()->getPost('goal')))
                    ->setDifficulty((int)$this->getRequest()->getPost('difficulty'))
                    ->setStatus((int)$this->getRequest()->getPost('status'))
                    ->setAccessType($accessType)
                    ->setPrice($accessType === ProgramModel::ACCESS_PAID ? $price : '0')
                    ->setCurrency($currency ?: 'EUR')
                    ->setReadAccessAll(in_array('all', $selectedGroups, true))
                    ->setGroupIds(array_intersect(array_map('intval', $selectedGroups), $groupIds));
                $id = $programMapper->save($program);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'structure', 'id' => $id]);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(array_merge(['action' => 'treat'], $program->getId() ? ['id' => $program->getId()] : []));
        }

        $this->getView()->set('program', $program)
            ->set('groups', $groups);
    }

    public function structureAction()
    {
        $programMapper = new ProgramMapper();
        $structureMapper = new ProgramStructureMapper();
        $program = $programMapper->getProgramById((int)$this->getRequest()->getParam('id'));

        if (!$program) {
            $this->redirect()
                ->withMessage('entryNotFound', 'danger')
                ->to(['action' => 'index']);
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuPrograms'), ['action' => 'index'])
            ->add($program->getTitle(), ['action' => 'treat', 'id' => $program->getId()])
            ->add($this->getTranslator()->trans('programStructure'), ['action' => 'structure', 'id' => $program->getId()]);

        $phases = $structureMapper->getPhasesOfProgram($program->getId());
        $workouts = $this->getSelectableWorkouts($phases);

        if ($this->getRequest()->getPost('generate')) {
            $this->generateWeeks($program->getId(), $phases, array_keys($workouts));
        }

        if ($this->getRequest()->getPost('saveStructure')) {
            $errorBag = new ErrorBag();
            $newPhases = $this->parsePhases((array)$this->getRequest()->getPost('phases'), array_keys($workouts), $errorBag);

            if (!$errorBag->hasErrors()) {
                $structureMapper->syncStructure($program->getId(), $newPhases);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'structure', 'id' => $program->getId()]);
            }

            $this->addMessage($errorBag->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($errorBag)
                ->to(['action' => 'structure', 'id' => $program->getId()]);
        }

        $this->getView()->set('program', $program)
            ->set('phases', $phases)
            ->set('workouts', $workouts)
            ->set('hasParticipants', $programMapper->hasParticipantsOrOrders($program->getId()))
            ->set('maxGeneratedWeeks', self::MAX_GENERATED_WEEKS);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $programMapper = new ProgramMapper();

            if ($programMapper->delete((int)$this->getRequest()->getParam('id'))) {
                $this->addMessage('deleteSuccess');
            } else {
                $this->addMessage('programInUse', 'warning');
            }
        }

        $this->redirect(['action' => 'index']);
    }

    /**
     * Appends weeks to the saved structure. Every new week gets the same sessions.
     *
     * @param int $programId
     * @param ProgramPhaseModel[] $phases current phases
     * @param int[] $allowedWorkoutIds
     */
    private function generateWeeks(int $programId, array $phases, array $allowedWorkoutIds): void
    {
        $weeks = (int)$this->getRequest()->getPost('weeks');

        $templates = [];
        foreach ((array)$this->getRequest()->getPost('template') as $row) {
            $row = (array)$row;
            $workoutId = (int)($row['workoutId'] ?? 0);
            if (in_array($workoutId, $allowedWorkoutIds, true)) {
                $templates[] = [
                    'workoutId' => $workoutId,
                    'title' => mb_substr(trim((string)($row['title'] ?? '')), 0, 255),
                    'dayHint' => WorkoutExerciseModel::toNullableInt($row['dayHint'] ?? null),
                ];
            }
        }

        if ($weeks < 1 || $weeks > self::MAX_GENERATED_WEEKS || empty($templates)) {
            $this->redirect()
                ->withMessage('generateInvalid', 'danger')
                ->to(['action' => 'structure', 'id' => $programId]);
        }

        $firstWeek = count($phases) + 1;
        for ($week = $firstWeek; $week < $firstWeek + $weeks; $week++) {
            $sessions = [];
            foreach ($templates as $template) {
                $sessions[] = (new ProgramSessionModel())
                    ->setWorkoutId($template['workoutId'])
                    ->setTitle($template['title'])
                    ->setDayHint($template['dayHint']);
            }

            $phases[] = (new ProgramPhaseModel())
                ->setTitle($this->getTranslator()->trans('weekNumber', $week))
                ->setSessions($sessions);
        }

        (new ProgramStructureMapper())->syncStructure($programId, $phases);

        $this->redirect()
            ->withMessage('generateSuccess')
            ->to(['action' => 'structure', 'id' => $programId]);
    }

    /**
     * Returns the workouts that can be chosen: all active ones and those the program already uses.
     *
     * @param ProgramPhaseModel[] $phases
     * @return array<int, \Modules\Fitness\Models\Workout> workout id => workout
     */
    private function getSelectableWorkouts(array $phases): array
    {
        $usedIds = [];
        foreach ($phases as $phase) {
            foreach ($phase->getSessions() as $session) {
                $usedIds[] = $session->getWorkoutId();
            }
        }

        $workouts = [];
        foreach ((new WorkoutMapper())->getWorkouts() as $workout) {
            if ($workout->isActive() || in_array($workout->getId(), $usedIds, true)) {
                $workouts[$workout->getId()] = $workout;
            }
        }

        return $workouts;
    }

    /**
     * Turns the submitted phases and sessions into models and adds an error for every invalid value.
     *
     * @param array $rows submitted phases in their order
     * @param int[] $allowedWorkoutIds
     * @param ErrorBag $errorBag
     * @return ProgramPhaseModel[]
     */
    private function parsePhases(array $rows, array $allowedWorkoutIds, ErrorBag $errorBag): array
    {
        $translator = $this->getTranslator();
        $phases = [];
        $phaseNumber = 0;

        foreach ($rows as $row) {
            $phaseNumber++;
            $row = (array)$row;

            $title = trim((string)($row['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 255) {
                $errorBag->addError('phases', $translator->trans('phaseTitleInvalid', $phaseNumber));
            }

            $sessions = [];
            $sessionNumber = 0;
            foreach ((array)($row['sessions'] ?? []) as $sessionRow) {
                $sessionNumber++;
                $sessionRow = (array)$sessionRow;

                $workoutId = (int)($sessionRow['workoutId'] ?? 0);
                if (!in_array($workoutId, $allowedWorkoutIds, true)) {
                    $errorBag->addError('phases', $translator->trans('sessionWorkoutMissing', $phaseNumber, $sessionNumber));
                }

                $sessionTitle = trim((string)($sessionRow['title'] ?? ''));
                if (mb_strlen($sessionTitle) > 255) {
                    $errorBag->addError('phases', $translator->trans('sessionTitleTooLong', $phaseNumber, $sessionNumber));
                }

                $sessions[] = (new ProgramSessionModel())
                    ->setId((int)($sessionRow['id'] ?? 0))
                    ->setWorkoutId($workoutId)
                    ->setTitle($sessionTitle)
                    ->setDayHint(WorkoutExerciseModel::toNullableInt($sessionRow['dayHint'] ?? null))
                    ->setOptional(!empty($sessionRow['optional']));
            }

            $phases[] = (new ProgramPhaseModel())
                ->setId((int)($row['id'] ?? 0))
                ->setTitle($title)
                ->setDescription(trim((string)($row['description'] ?? '')))
                ->setSessions($sessions);
        }

        return $phases;
    }
}
