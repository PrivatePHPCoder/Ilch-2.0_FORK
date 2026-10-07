<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;
use Modules\Fitness\Models\Exercise as ExerciseModel;

class Exercises extends Base
{
    public function indexAction()
    {
        $exerciseMapper = new ExerciseMapper();
        $muscleGroupMapper = new MuscleGroupMapper();

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuExercises'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') === 'delete' && $this->getRequest()->getPost('check_exercises')) {
            $notDeleted = 0;
            foreach ($this->getRequest()->getPost('check_exercises') as $id) {
                if (!$exerciseMapper->delete((int)$id)) {
                    $notDeleted++;
                }
            }

            $this->redirect()
                ->withMessage($notDeleted ? 'exercisesInUse' : 'deleteSuccess', $notDeleted ? 'warning' : 'success')
                ->to(['action' => 'index']);
        }

        if ($this->getRequest()->getPost('saveOrder')) {
            $exerciseMapper->updatePositions((array)$this->getRequest()->getPost('items'));

            $this->redirect()
                ->withMessage('saveSuccess')
                ->to(['action' => 'index']);
        }

        $muscleGroupNames = [];
        foreach ($muscleGroupMapper->getMuscleGroups() as $muscleGroup) {
            $muscleGroupNames[$muscleGroup->getId()] = $muscleGroup->getName();
        }

        $this->getView()->set('exercises', $exerciseMapper->getExercises())
            ->set('muscleGroupNames', $muscleGroupNames);
    }

    public function treatAction()
    {
        $exerciseMapper = new ExerciseMapper();
        $categoryMapper = new CategoryMapper();
        $muscleGroupMapper = new MuscleGroupMapper();
        $exercise = new ExerciseModel();

        if ($this->getRequest()->getParam('id')) {
            $exercise = $exerciseMapper->getExerciseById((int)$this->getRequest()->getParam('id'));

            if (!$exercise) {
                $this->redirect()
                    ->withMessage('entryNotFound', 'danger')
                    ->to(['action' => 'index']);
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuExercises'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($exercise->getId() ? 'edit' : 'add'), array_merge(['action' => 'treat'], $exercise->getId() ? ['id' => $exercise->getId()] : []));

        $categories = $categoryMapper->getCategories();
        $muscleGroups = $muscleGroupMapper->getMuscleGroups();

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'title' => 'required|max:255,string',
                'categoryId' => 'integer|min:0',
                'difficulty' => 'required|integer|min:1|max:3',
                'primaryMuscleGroup' => 'integer|min:0',
                'image' => 'max:255,string',
                'videoUrl' => 'url|max:255,string',
                'isPublic' => 'required|integer|min:0|max:1',
                'active' => 'required|integer|min:0|max:1',
            ]);

            $image = trim((string)$this->getRequest()->getPost('image'));
            if (!$this->isValidImage($image)) {
                $validation->getErrorBag()->addError('image', $this->getTranslator()->trans('invalidImage'));
            }

            if ($validation->isValid()) {
                // Only accept ids of categories and muscle groups that exist.
                $categoryIds = array_map(static fn ($category) => $category->getId(), $categories);
                $muscleGroupIds = array_map(static fn ($muscleGroup) => $muscleGroup->getId(), $muscleGroups);

                $categoryId = (int)$this->getRequest()->getPost('categoryId');
                $primaryMuscleGroup = (int)$this->getRequest()->getPost('primaryMuscleGroup');
                $selectedMuscleGroups = array_intersect(array_map('intval', (array)$this->getRequest()->getPost('muscleGroups')), $muscleGroupIds);

                $exercise->setTitle(trim($this->getRequest()->getPost('title')))
                    ->setCategoryId(in_array($categoryId, $categoryIds, true) ? $categoryId : null)
                    ->setDifficulty((int)$this->getRequest()->getPost('difficulty'))
                    ->setDescription((string)$this->getRequest()->getPost('description'))
                    ->setInstructions((string)$this->getRequest()->getPost('instructions'))
                    ->setNotes(trim((string)$this->getRequest()->getPost('notes')))
                    ->setImage($image)
                    ->setVideoUrl(trim((string)$this->getRequest()->getPost('videoUrl')))
                    ->setPublic((bool)$this->getRequest()->getPost('isPublic'))
                    ->setActive((bool)$this->getRequest()->getPost('active'))
                    ->setMuscleGroups($selectedMuscleGroups, in_array($primaryMuscleGroup, $muscleGroupIds, true) ? $primaryMuscleGroup : null);
                $exerciseMapper->save($exercise);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(array_merge(['action' => 'treat'], $exercise->getId() ? ['id' => $exercise->getId()] : []));
        }

        $this->getView()->set('exercise', $exercise)
            ->set('categories', $categories)
            ->set('muscleGroups', $muscleGroups);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $exerciseMapper = new ExerciseMapper();

            if ($exerciseMapper->delete((int)$this->getRequest()->getParam('id'))) {
                $this->addMessage('deleteSuccess');
            } else {
                $this->addMessage('exerciseInUse', 'warning');
            }
        }

        $this->redirect(['action' => 'index']);
    }
}
