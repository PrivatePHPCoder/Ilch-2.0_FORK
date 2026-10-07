<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;

class Index extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index']);

        $this->getView()->set('ownLayout', (bool)$this->getConfig()->get('fitness_ownLayout'))
            ->set('counts', [
                'exercises' => count((new ExerciseMapper())->getExercises()),
                'categories' => count((new CategoryMapper())->getCategories()),
                'musclegroups' => count((new MuscleGroupMapper())->getMuscleGroups()),
            ]);
    }
}
