<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Exercise as ExerciseModel;
use Modules\Fitness\Models\MuscleGroup as MuscleGroupModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

class MuscleGroupTest extends DatabaseTestCase
{
    protected MuscleGroup $out;

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new MuscleGroup();
    }

    public function testSaveAndUpdateMuscleGroup()
    {
        $id = $this->out->save((new MuscleGroupModel())->setName('Brust'));

        $muscleGroup = $this->out->getMuscleGroupById($id);
        $muscleGroup->setName('Brustmuskel');
        $this->out->save($muscleGroup);

        self::assertSame('Brustmuskel', $this->out->getMuscleGroupById($id)->getName());
        self::assertCount(1, $this->out->getMuscleGroups());
    }

    public function testUpdatePositionsChangesOrder()
    {
        $first = $this->out->save((new MuscleGroupModel())->setName('Beine'));
        $second = $this->out->save((new MuscleGroupModel())->setName('Rücken'));

        $this->out->updatePositions([$second, $first]);

        $names = array_map(static fn ($muscleGroup) => $muscleGroup->getName(), $this->out->getMuscleGroups());
        self::assertSame(['Rücken', 'Beine'], $names);
    }

    public function testExerciseCountAndDeleteRemovesAssignment()
    {
        $legs = $this->out->save((new MuscleGroupModel())->setName('Beine'));
        $back = $this->out->save((new MuscleGroupModel())->setName('Rücken'));
        $exerciseMapper = new Exercise();
        $exerciseId = $exerciseMapper->save((new ExerciseModel())->setTitle('Kreuzheben')->setMuscleGroups([$back], $legs));

        self::assertSame(1, $this->out->getMuscleGroupById($legs)->getExerciseCount());

        $this->out->delete($legs);

        $exercise = $exerciseMapper->getExerciseById($exerciseId);
        self::assertSame([$back], $exercise->getMuscleGroupIds());
        self::assertNull($exercise->getPrimaryMuscleGroupId());
    }

    /**
     * Returns database schema sql statements to initialize database
     *
     * @return string
     */
    protected static function getSchemaSQLQueries(): string
    {
        $config = new ModuleConfig();
        $userConfig = new UserConfig();
        $adminConfig = new AdminConfig();

        return $adminConfig->getInstallSql() . $userConfig->getInstallSql() . $config->getInstallSql();
    }
}
