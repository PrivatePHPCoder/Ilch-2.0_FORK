<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Difficulty;
use Modules\Fitness\Models\Exercise as ExerciseModel;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\Fitness\Models\WorkoutExercise as WorkoutExerciseModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

class WorkoutTest extends DatabaseTestCase
{
    protected Workout $out;

    /**
     * @var int[]
     */
    private array $exerciseIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Workout();

        $exerciseMapper = new Exercise();
        foreach (['Kniebeuge', 'Liegestütz', 'Klimmzug'] as $title) {
            $this->exerciseIds[$title] = $exerciseMapper->save((new ExerciseModel())->setTitle($title));
        }
    }

    public function testSaveWorkoutWithExercises()
    {
        $id = $this->out->save((new WorkoutModel())
            ->setTitle('Training A')
            ->setDurationMin(45)
            ->setDifficulty(Difficulty::INTERMEDIATE)
            ->setExercises([
                $this->item('Kniebeuge')->setSets(3)->setRepsMin(8)->setRepsMax(12)->setWeight('60 kg')->setRestSec(90),
                $this->item('Liegestütz')->setSets(3)->setRepsMin(15)->setRepsMax(15),
            ]));

        $workout = $this->out->getWorkoutById($id);
        self::assertSame('Training A', $workout->getTitle());
        self::assertSame(45, $workout->getDurationMin());
        self::assertSame(Difficulty::INTERMEDIATE, $workout->getDifficulty());
        self::assertSame(2, $workout->getExerciseCount());

        $items = $workout->getExercises();
        self::assertSame('Kniebeuge', $items[0]->getExerciseTitle());
        self::assertSame(3, $items[0]->getSets());
        self::assertSame('8–12', $items[0]->getRepsText());
        self::assertSame('60 kg', $items[0]->getWeight());
        self::assertSame(90, $items[0]->getRestSec());
        self::assertNull($items[0]->getDurationSec());
        self::assertSame('Liegestütz', $items[1]->getExerciseTitle());
        self::assertSame('15', $items[1]->getRepsText());
    }

    public function testSameExerciseCanBeUsedTwice()
    {
        $id = $this->out->save((new WorkoutModel())
            ->setTitle('Zirkel')
            ->setExercises([$this->item('Kniebeuge'), $this->item('Liegestütz'), $this->item('Kniebeuge')]));

        $titles = array_map(static fn ($item) => $item->getExerciseTitle(), $this->out->getWorkoutById($id)->getExercises());
        self::assertSame(['Kniebeuge', 'Liegestütz', 'Kniebeuge'], $titles);
    }

    /**
     * Rows that stay keep their id, so later training logs keep their link.
     */
    public function testSyncKeepsIdsReordersRemovesAndAdds()
    {
        $id = $this->out->save((new WorkoutModel())
            ->setTitle('Training A')
            ->setExercises([$this->item('Kniebeuge'), $this->item('Liegestütz'), $this->item('Klimmzug')]));

        [$squat, $pushUp, $pullUp] = $this->out->getWorkoutById($id)->getExercises();

        $workout = $this->out->getWorkoutById($id);
        $workout->setExercises([
            (clone $pullUp)->setSets(4),
            clone $squat,
            $this->item('Liegestütz')->setNotes('neu'),
        ]);
        $this->out->save($workout);

        $items = $this->out->getWorkoutById($id)->getExercises();
        self::assertCount(3, $items);
        self::assertSame($pullUp->getId(), $items[0]->getId());
        self::assertSame(4, $items[0]->getSets());
        self::assertSame($squat->getId(), $items[1]->getId());
        self::assertNotSame($pushUp->getId(), $items[2]->getId());
        self::assertSame('neu', $items[2]->getNotes());
        self::assertSame([0, 1, 2], array_map(static fn ($item) => $item->getPosition(), $items));
    }

    /**
     * The query builder skips null values. Removed values must still be cleared.
     */
    public function testClearedValuesAreStoredAsNull()
    {
        $id = $this->out->save((new WorkoutModel())
            ->setTitle('Training A')
            ->setDurationMin(30)
            ->setExercises([$this->item('Kniebeuge')->setSets(3)->setRepsMin(10)->setRestSec(60)]));

        $workout = $this->out->getWorkoutById($id);
        $item = $workout->getExercises()[0];
        $workout->setDurationMin(null)
            ->setExercises([$item->setSets(null)->setRepsMin(null)->setRestSec(null)]);
        $this->out->save($workout);

        $workout = $this->out->getWorkoutById($id);
        self::assertNull($workout->getDurationMin());
        self::assertNull($workout->getExercises()[0]->getSets());
        self::assertNull($workout->getExercises()[0]->getRepsMin());
        self::assertNull($workout->getExercises()[0]->getRestSec());
    }

    public function testSavingWithoutLoadedExercisesKeepsThem()
    {
        $id = $this->out->save((new WorkoutModel())
            ->setTitle('Training A')
            ->setExercises([$this->item('Kniebeuge'), $this->item('Klimmzug')]));

        $workout = $this->out->getWorkoutById($id, false);
        $workout->setTitle('Training A neu');
        $this->out->save($workout);

        $workout = $this->out->getWorkoutById($id);
        self::assertSame('Training A neu', $workout->getTitle());
        self::assertSame(2, $workout->getExerciseCount());
    }

    public function testListShowsExerciseCount()
    {
        $this->out->save((new WorkoutModel())->setTitle('B')->setExercises([$this->item('Kniebeuge')]));
        $this->out->save((new WorkoutModel())->setTitle('A'));

        $workouts = $this->out->getWorkouts();
        self::assertSame(['A', 'B'], array_map(static fn ($workout) => $workout->getTitle(), $workouts));
        self::assertSame([0, 1], array_map(static fn ($workout) => $workout->getExerciseCount(), $workouts));
    }

    public function testDeleteRemovesRowsButKeepsExercises()
    {
        $id = $this->out->save((new WorkoutModel())->setTitle('Training A')->setExercises([$this->item('Kniebeuge')]));

        self::assertTrue($this->out->delete($id));
        self::assertNull($this->out->getWorkoutById($id));
        self::assertSame(0, (int)$this->db->select('COUNT(*)')->from('fitness_workout_exercises')->execute()->fetchCell());
        self::assertNotNull((new Exercise())->getExerciseById($this->exerciseIds['Kniebeuge']));
        self::assertFalse((new Exercise())->isUsedInWorkouts($this->exerciseIds['Kniebeuge']));
    }

    public function testWorkoutUsedInProgramIsNotDeleted()
    {
        $id = $this->out->save((new WorkoutModel())->setTitle('Training A'));
        $programId = $this->db->insert('fitness_programs')
            ->values(['title' => 'Programm', 'description' => '', 'created_at' => '2026-10-07 10:00:00'])
            ->execute();
        $phaseId = $this->db->insert('fitness_program_phases')
            ->values(['program_id' => $programId, 'title' => 'Woche 1', 'description' => ''])
            ->execute();
        $this->db->insert('fitness_program_sessions')
            ->values(['program_id' => $programId, 'phase_id' => $phaseId, 'workout_id' => $id, 'title' => 'Training A'])
            ->execute();

        self::assertTrue($this->out->isUsedInPrograms($id));
        self::assertFalse($this->out->delete($id));
        self::assertNotNull($this->out->getWorkoutById($id));
    }

    public function testRepsText()
    {
        self::assertSame('', (new WorkoutExerciseModel())->getRepsText());
        self::assertSame('10', (new WorkoutExerciseModel())->setRepsMin(10)->getRepsText());
        self::assertSame('12', (new WorkoutExerciseModel())->setRepsMax(12)->getRepsText());
        self::assertSame('8–12', (new WorkoutExerciseModel())->setRepsMin(8)->setRepsMax(12)->getRepsText());
    }

    private function item(string $exerciseTitle): WorkoutExerciseModel
    {
        return (new WorkoutExerciseModel())->setExerciseId($this->exerciseIds[$exerciseTitle]);
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
