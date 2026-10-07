<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Category as CategoryModel;
use Modules\Fitness\Models\Exercise as ExerciseModel;
use Modules\Fitness\Models\MuscleGroup as MuscleGroupModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

class ExerciseTest extends DatabaseTestCase
{
    protected Exercise $out;

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Exercise();
    }

    public function testSaveNewExercise()
    {
        $categoryId = (new Category())->save((new CategoryModel())->setName('Kraft'));
        $muscleGroupMapper = new MuscleGroup();
        $legs = $muscleGroupMapper->save((new MuscleGroupModel())->setName('Beine'));
        $glutes = $muscleGroupMapper->save((new MuscleGroupModel())->setName('Gesäß'));

        $id = $this->out->save((new ExerciseModel())
            ->setTitle('Kniebeuge')
            ->setCategoryId($categoryId)
            ->setDifficulty(ExerciseModel::DIFFICULTY_INTERMEDIATE)
            ->setDescription('<p>Beschreibung</p>')
            ->setInstructions('<p>Anleitung</p>')
            ->setNotes('Rücken gerade halten')
            ->setImage('application/modules/media/static/upload/kniebeuge.jpg')
            ->setVideoUrl('https://www.youtube.com/watch?v=abc')
            ->setPublic(true)
            ->setMuscleGroups([$glutes], $legs));

        $exercise = $this->out->getExerciseById($id);
        self::assertNotNull($exercise);
        self::assertSame('Kniebeuge', $exercise->getTitle());
        self::assertSame($categoryId, $exercise->getCategoryId());
        self::assertSame('Kraft', $exercise->getCategoryName());
        self::assertSame(ExerciseModel::DIFFICULTY_INTERMEDIATE, $exercise->getDifficulty());
        self::assertSame('Rücken gerade halten', $exercise->getNotes());
        self::assertSame('https://www.youtube.com/watch?v=abc', $exercise->getVideoUrl());
        self::assertTrue($exercise->isPublic());
        self::assertTrue($exercise->isActive());
        self::assertSame($legs, $exercise->getPrimaryMuscleGroupId());
        self::assertEqualsCanonicalizing([$legs, $glutes], $exercise->getMuscleGroupIds());
        self::assertNotSame('', $exercise->getCreatedAt());
        self::assertNull($exercise->getUpdatedAt());
    }

    /**
     * The query builder skips null values. Removing the category must still work.
     */
    public function testUpdateCanRemoveCategory()
    {
        $categoryId = (new Category())->save((new CategoryModel())->setName('Kraft'));
        $id = $this->out->save((new ExerciseModel())->setTitle('Kniebeuge')->setCategoryId($categoryId));

        $exercise = $this->out->getExerciseById($id);
        $exercise->setCategoryId(null);
        $this->out->save($exercise);

        $exercise = $this->out->getExerciseById($id);
        self::assertNull($exercise->getCategoryId());
        self::assertSame('', $exercise->getCategoryName());
    }

    public function testUpdateKeepsCreatedAtAndSetsUpdatedAt()
    {
        $id = $this->out->save((new ExerciseModel())->setTitle('Kniebeuge')->setCreatedAt('2026-01-01 10:00:00'));

        $exercise = $this->out->getExerciseById($id);
        $exercise->setTitle('Tiefe Kniebeuge');
        $this->out->save($exercise);

        $exercise = $this->out->getExerciseById($id);
        self::assertSame('Tiefe Kniebeuge', $exercise->getTitle());
        self::assertSame('2026-01-01 10:00:00', $exercise->getCreatedAt());
        self::assertNotNull($exercise->getUpdatedAt());
    }

    public function testUpdateReplacesMuscleGroups()
    {
        $muscleGroupMapper = new MuscleGroup();
        $legs = $muscleGroupMapper->save((new MuscleGroupModel())->setName('Beine'));
        $back = $muscleGroupMapper->save((new MuscleGroupModel())->setName('Rücken'));
        $id = $this->out->save((new ExerciseModel())->setTitle('Kreuzheben')->setMuscleGroups([$legs], $back));

        $exercise = $this->out->getExerciseById($id);
        $exercise->setMuscleGroups([], $legs);
        $this->out->save($exercise);

        $exercise = $this->out->getExerciseById($id);
        self::assertSame([$legs], $exercise->getMuscleGroupIds());
        self::assertSame($legs, $exercise->getPrimaryMuscleGroupId());
    }

    public function testNewExercisesAreAddedAtTheEndAndCanBeSorted()
    {
        $first = $this->out->save((new ExerciseModel())->setTitle('Liegestütz'));
        $second = $this->out->save((new ExerciseModel())->setTitle('Klimmzug'));

        $titles = array_map(static fn ($exercise) => $exercise->getTitle(), $this->out->getExercises());
        self::assertSame(['Liegestütz', 'Klimmzug'], $titles);

        $this->out->updatePositions([$second, $first]);

        $titles = array_map(static fn ($exercise) => $exercise->getTitle(), $this->out->getExercises());
        self::assertSame(['Klimmzug', 'Liegestütz'], $titles);
    }

    public function testDeleteRemovesExerciseAndMuscleGroups()
    {
        $legs = (new MuscleGroup())->save((new MuscleGroupModel())->setName('Beine'));
        $id = $this->out->save((new ExerciseModel())->setTitle('Kniebeuge')->setMuscleGroups([], $legs));

        self::assertTrue($this->out->delete($id));
        self::assertNull($this->out->getExerciseById($id));
        self::assertSame(0, (int)$this->db->select('COUNT(*)')->from('fitness_exercise_muscles')->execute()->fetchCell());
    }

    public function testExerciseUsedInWorkoutIsNotDeleted()
    {
        $id = $this->out->save((new ExerciseModel())->setTitle('Kniebeuge'));
        $workoutId = $this->db->insert('fitness_workouts')
            ->values(['title' => 'Training A', 'description' => '', 'created_at' => '2026-10-07 10:00:00'])
            ->execute();
        $this->db->insert('fitness_workout_exercises')
            ->values(['workout_id' => $workoutId, 'exercise_id' => $id, 'notes' => ''])
            ->execute();

        self::assertTrue($this->out->isUsedInWorkouts($id));
        self::assertFalse($this->out->delete($id));
        self::assertNotNull($this->out->getExerciseById($id));
    }

    public function testModelDifficultyFallsBackToBeginner()
    {
        $exercise = (new ExerciseModel())->setDifficulty(99);

        self::assertSame(ExerciseModel::DIFFICULTY_BEGINNER, $exercise->getDifficulty());
        self::assertSame('difficultyBeginner', $exercise->getDifficultyKey());
    }

    public function testModelMuscleGroupsIncludePrimaryWithoutDuplicates()
    {
        $exercise = (new ExerciseModel())->setMuscleGroups(['3', 3, 0, 5], 5);

        self::assertSame([3, 5], $exercise->getMuscleGroupIds());
        self::assertSame(5, $exercise->getPrimaryMuscleGroupId());
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
