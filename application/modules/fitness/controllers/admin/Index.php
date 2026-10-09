<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\Milestone as MilestoneMapper;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;
use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\Workout as WorkoutMapper;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Service\SampleData;

class Index extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index']);

        $sampleData = new SampleData($this->getConfig());

        $this->getView()->set('ownLayout', (bool)$this->getConfig()->get('fitness_ownLayout'))
            ->set('sampleCounts', array_map('count', $sampleData->getIds()))
            ->set('sampleParticipants', $sampleData->getParticipantCount())
            ->set('counts', [
                'exercises' => count((new ExerciseMapper())->getExercises()),
                'workouts' => count((new WorkoutMapper())->getWorkouts()),
                'programs' => count((new ProgramMapper())->getPrograms()),
                'participants' => array_sum((new EnrollmentMapper())->getCountsPerProgram()),
                'orders' => (new OrderMapper())->getCountsPerStatus()[OrderModel::STATUS_OPEN],
                'milestones' => count((new MilestoneMapper())->getMilestones()),
                'categories' => count((new CategoryMapper())->getCategories()),
                'musclegroups' => count((new MuscleGroupMapper())->getMuscleGroups()),
            ]);
    }
}
