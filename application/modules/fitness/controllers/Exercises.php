<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;

/**
 * The public exercise library. It only shows active exercises that are released for it.
 */
class Exercises extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($this->getTranslator()->trans('exerciseLibrary'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('exerciseLibrary'), ['action' => 'index']);

        $categoryId = (int)$this->getRequest()->getParam('category');
        $muscleGroupId = (int)$this->getRequest()->getParam('muscle');

        $exercises = (new ExerciseMapper())->getEntriesBy(['e.active' => 1, 'e.is_public' => 1]);

        // Only offer filters that lead to at least one exercise.
        $usedCategoryIds = array_unique(array_map(static fn ($exercise) => $exercise->getCategoryId(), $exercises));
        $usedMuscleGroupIds = array_unique(array_merge([], ...array_map(static fn ($exercise) => $exercise->getMuscleGroupIds(), $exercises)));
        $categories = array_filter((new CategoryMapper())->getCategories(), static fn ($category) => in_array($category->getId(), $usedCategoryIds, true));
        $muscleGroups = [];
        foreach ((new MuscleGroupMapper())->getMuscleGroups() as $muscleGroup) {
            $muscleGroups[$muscleGroup->getId()] = $muscleGroup;
        }

        if ($categoryId) {
            $exercises = array_filter($exercises, static fn ($exercise) => $exercise->getCategoryId() === $categoryId);
        }
        if ($muscleGroupId) {
            $exercises = array_filter($exercises, static fn ($exercise) => in_array($muscleGroupId, $exercise->getMuscleGroupIds(), true));
        }

        $this->getView()->set('exercises', array_values($exercises))
            ->set('categories', $categories)
            ->set('muscleGroups', $muscleGroups)
            ->set('filterMuscleGroups', array_intersect_key($muscleGroups, array_flip($usedMuscleGroupIds)))
            ->set('categoryId', $categoryId)
            ->set('muscleGroupId', $muscleGroupId);
    }

    public function showAction()
    {
        $exercise = (new ExerciseMapper())->getExerciseById((int)$this->getRequest()->getParam('id'));
        $isPreview = false;

        if (!$exercise || !$exercise->isActive() || !$exercise->isPublic()) {
            if (!$exercise || !$this->canManageFitness()) {
                $this->redirect()
                    ->withMessage('exerciseNotFound', 'warning')
                    ->to(['action' => 'index']);
            }
            $isPreview = true;
        }

        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($exercise->getTitle());
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('exerciseLibrary'), ['action' => 'index'])
            ->add($exercise->getTitle(), ['action' => 'show', 'id' => $exercise->getId()]);

        $muscleGroups = [];
        foreach ((new MuscleGroupMapper())->getMuscleGroups() as $muscleGroup) {
            $muscleGroups[$muscleGroup->getId()] = $muscleGroup;
        }

        $this->getView()->set('exercise', $exercise)
            ->set('muscleGroups', $muscleGroups)
            ->set('isPreview', $isPreview);
    }
}
