<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Ilch\Validation\ErrorBag;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\Workout as WorkoutMapper;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\Fitness\Models\WorkoutExercise as WorkoutExerciseModel;

class Workouts extends Base
{
    /**
     * Allowed range of the number fields of an exercise row: field => [min, max, translation key].
     *
     * @var array<string, array{int, int, string}>
     */
    private const NUMBER_FIELDS = [
        'sets' => [1, 99, 'sets'],
        'repsMin' => [0, 999, 'repsMin'],
        'repsMax' => [0, 999, 'repsMax'],
        'durationSec' => [0, 86400, 'durationSec'],
        'restSec' => [0, 3600, 'restSec'],
    ];

    public function indexAction()
    {
        $workoutMapper = new WorkoutMapper();

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuWorkouts'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') === 'delete' && $this->getRequest()->getPost('check_workouts')) {
            $notDeleted = 0;
            foreach ($this->getRequest()->getPost('check_workouts') as $id) {
                if (!$workoutMapper->delete((int)$id)) {
                    $notDeleted++;
                }
            }

            $this->redirect()
                ->withMessage($notDeleted ? 'workoutsInUse' : 'deleteSuccess', $notDeleted ? 'warning' : 'success')
                ->to(['action' => 'index']);
        }

        $this->getView()->set('workouts', $workoutMapper->getWorkouts());
    }

    public function treatAction()
    {
        $workoutMapper = new WorkoutMapper();
        $workout = new WorkoutModel();

        if ($this->getRequest()->getParam('id')) {
            $workout = $workoutMapper->getWorkoutById((int)$this->getRequest()->getParam('id'));

            if (!$workout) {
                $this->redirect()
                    ->withMessage('entryNotFound', 'danger')
                    ->to(['action' => 'index']);
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuWorkouts'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($workout->getId() ? 'edit' : 'add'), array_merge(['action' => 'treat'], $workout->getId() ? ['id' => $workout->getId()] : []));

        $exercises = $this->getSelectableExercises($workout);

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'title' => 'required|max:255,string',
                'difficulty' => 'required|integer|min:1|max:3',
                'durationMin' => 'integer|min:1|max:1440',
                'active' => 'required|integer|min:0|max:1',
            ]);

            $items = $this->parseItems(
                (array)$this->getRequest()->getPost('items'),
                array_keys($exercises),
                $validation->getErrorBag()
            );

            if ($validation->isValid()) {
                $workout->setTitle(trim($this->getRequest()->getPost('title')))
                    ->setDescription((string)$this->getRequest()->getPost('description'))
                    ->setDifficulty((int)$this->getRequest()->getPost('difficulty'))
                    ->setDurationMin(WorkoutExerciseModel::toNullableInt($this->getRequest()->getPost('durationMin')))
                    ->setActive((bool)$this->getRequest()->getPost('active'))
                    ->setExercises($items);
                $workoutMapper->save($workout);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(array_merge(['action' => 'treat'], $workout->getId() ? ['id' => $workout->getId()] : []));
        }

        $this->getView()->set('workout', $workout)
            ->set('exercises', $exercises);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $workoutMapper = new WorkoutMapper();

            if ($workoutMapper->delete((int)$this->getRequest()->getParam('id'))) {
                $this->addMessage('deleteSuccess');
            } else {
                $this->addMessage('workoutInUse', 'warning');
            }
        }

        $this->redirect(['action' => 'index']);
    }

    /**
     * Returns the exercises that can be chosen: all active ones and those the workout already uses.
     *
     * @param WorkoutModel $workout
     * @return array<int, \Modules\Fitness\Models\Exercise> exercise id => exercise
     */
    private function getSelectableExercises(WorkoutModel $workout): array
    {
        $usedIds = array_map(static fn ($item) => $item->getExerciseId(), $workout->getExercises());

        $exercises = [];
        foreach ((new ExerciseMapper())->getEntriesBy([], ['e.title' => 'ASC']) as $exercise) {
            if ($exercise->isActive() || in_array($exercise->getId(), $usedIds, true)) {
                $exercises[$exercise->getId()] = $exercise;
            }
        }

        return $exercises;
    }

    /**
     * Turns the submitted rows into workout exercises and adds an error for every invalid value.
     *
     * @param array $rows submitted rows in their order
     * @param int[] $allowedExerciseIds
     * @param ErrorBag $errorBag
     * @return WorkoutExerciseModel[]
     */
    private function parseItems(array $rows, array $allowedExerciseIds, ErrorBag $errorBag): array
    {
        $translator = $this->getTranslator();
        $items = [];
        $rowNumber = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            $row = (array)$row;

            $exerciseId = (int)($row['exerciseId'] ?? 0);
            if (!in_array($exerciseId, $allowedExerciseIds, true)) {
                $errorBag->addError('items', $translator->trans('itemExerciseMissing', $rowNumber));
            }

            $numbers = [];
            foreach (self::NUMBER_FIELDS as $field => [$min, $max, $labelKey]) {
                $value = trim((string)($row[$field] ?? ''));
                if ($value !== '' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < $min || (int)$value > $max)) {
                    $errorBag->addError('items', $translator->trans('itemNumberInvalid', $rowNumber, $translator->trans($labelKey), $min, $max));
                    $value = '';
                }
                $numbers[$field] = WorkoutExerciseModel::toNullableInt($value);
            }

            if ($numbers['repsMin'] !== null && $numbers['repsMax'] !== null && $numbers['repsMin'] > $numbers['repsMax']) {
                $errorBag->addError('items', $translator->trans('itemRepsOrder', $rowNumber));
            }

            $weight = trim((string)($row['weight'] ?? ''));
            if (mb_strlen($weight) > 50) {
                $errorBag->addError('items', $translator->trans('itemTextTooLong', $rowNumber, $translator->trans('weight'), 50));
            }

            $notes = trim((string)($row['notes'] ?? ''));
            if (mb_strlen($notes) > 500) {
                $errorBag->addError('items', $translator->trans('itemTextTooLong', $rowNumber, $translator->trans('notes'), 500));
            }

            $items[] = (new WorkoutExerciseModel())
                ->setId((int)($row['id'] ?? 0))
                ->setExerciseId($exerciseId)
                ->setSets($numbers['sets'])
                ->setRepsMin($numbers['repsMin'])
                ->setRepsMax($numbers['repsMax'])
                ->setWeight($weight)
                ->setDurationSec($numbers['durationSec'])
                ->setRestSec($numbers['restSec'])
                ->setNotes($notes);
        }

        return $items;
    }
}
