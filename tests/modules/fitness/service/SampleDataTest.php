<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use Ilch\Config\Database as DatabaseConfig;
use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\Workout as WorkoutMapper;
use Modules\Fitness\Models\Category as CategoryModel;
use Modules\Fitness\Models\Exercise as ExerciseModel;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\Fitness\Models\WorkoutExercise as WorkoutExerciseModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

/**
 * Adding and removing the sample data. Own content must never be lost.
 */
class SampleDataTest extends DatabaseTestCase
{
    protected SampleData $out;

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new SampleData(new DatabaseConfig($this->db));
    }

    public function testInstallAddsCompleteContentOnce()
    {
        $counts = $this->out->install('de_DE');

        self::assertSame(['programs' => 3, 'workouts' => 5, 'exercises' => 12, 'categories' => 3, 'muscleGroups' => 7], $counts);
        self::assertTrue($this->out->isInstalled());
        self::assertNull($this->out->install('de_DE'), 'Sample data is only added once.');
        self::assertCount(12, (new ExerciseMapper())->getExercises());

        $programs = (new ProgramMapper())->getPrograms();
        self::assertSame(['Fit in 4 Wochen', 'Kraft-Aufbau 6 Wochen', 'Cardio-Kick 2 Wochen'], array_map(static fn ($program) => $program->getTitle(), $programs));
        self::assertTrue($programs[1]->isPaid());
        self::assertSame('19.90', $programs[1]->getPrice());
        self::assertCount(4, (new ProgramStructureMapper())->getPhasesOfProgram($programs[0]->getId()));

        $squat = (new ExerciseMapper())->getExerciseById($this->out->getIds()['exercises'][0]);
        self::assertSame('Kniebeuge', $squat->getTitle());
        self::assertSame('application/modules/fitness/static/sample/squat.svg', $squat->getImage());
        self::assertNotNull(Media::videoEmbed($squat->getVideoUrl()));
    }

    public function testOtherLanguagesGetEnglishContent()
    {
        $this->out->install('en_EN');

        $titles = array_map(static fn ($exercise) => $exercise->getTitle(), (new ExerciseMapper())->getExercises());
        self::assertContains('Push-up', $titles);
        self::assertNotContains('Liegestütz', $titles);
    }

    public function testExistingCategoriesAndMuscleGroupsAreUsed()
    {
        $ownCategory = (new CategoryMapper())->save((new CategoryModel())->setName('kraft'));

        $counts = $this->out->install('de_DE');

        self::assertSame(2, $counts['categories'], 'The existing category "Kraft" is used, case does not matter.');
        self::assertNotContains($ownCategory, $this->out->getIds()['categories']);

        $this->out->remove();
        self::assertNotNull((new CategoryMapper())->getCategoryById($ownCategory), 'An own category is never removed.');
    }

    public function testRemoveDeletesAllSampleDataButNoOwnContent()
    {
        $ownExercise = (new ExerciseMapper())->save((new ExerciseModel())->setTitle('Eigene Übung'));
        $total = array_sum($this->out->install('de_DE'));

        self::assertSame(['deleted' => $total, 'kept' => 0], $this->out->remove());
        self::assertFalse($this->out->isInstalled());
        self::assertNull((new DatabaseConfig($this->db))->get(SampleData::CONFIG_KEY, true));
        self::assertSame([], (new ProgramMapper())->getPrograms());
        self::assertSame([], (new WorkoutMapper())->getWorkouts());
        self::assertSame([$ownExercise], array_map(static fn ($exercise) => $exercise->getId(), (new ExerciseMapper())->getExercises()));
        self::assertSame([], (new CategoryMapper())->getCategories());
    }

    public function testEntriesUsedByOwnContentAreKept()
    {
        $this->out->install('de_DE');
        $squatId = $this->out->getIds()['exercises'][0];
        $ownWorkout = (new WorkoutMapper())->save((new WorkoutModel())
            ->setTitle('Eigenes Workout')
            ->setExercises([(new WorkoutExerciseModel())->setExerciseId($squatId)]));

        $result = $this->out->remove();

        $squat = (new ExerciseMapper())->getExerciseById($squatId);
        self::assertNotNull($squat, 'The exercise of the own workout stays.');
        self::assertNotNull((new CategoryMapper())->getCategoryById((int)$squat->getCategoryId()), 'Its category stays as well.');
        self::assertGreaterThan(0, $result['kept']);
        self::assertTrue($this->out->isInstalled(), 'Kept entries are still known as sample data.');

        (new WorkoutMapper())->delete($ownWorkout);
        self::assertSame(0, $this->out->remove()['kept'], 'Once the own workout is gone, the rest can be removed.');
        self::assertFalse($this->out->isInstalled());
    }

    public function testParticipationsAreRemovedButProgramsWithOrdersStay()
    {
        $this->db->insert('users')
            ->values(['id' => 5, 'name' => 'Anna', 'password' => '', 'email' => 'anna@example.org', 'date_created' => '2026-10-09 10:00:00', 'confirmed' => 1, 'locale' => ''])
            ->execute();
        $this->out->install('de_DE');
        [$starterId, $strengthId] = $this->out->getIds()['programs'];
        $enrollment = (new EnrollmentMapper())->enroll($starterId, 5);
        (new Orders())->placeOrder((new ProgramMapper())->getProgramById($strengthId), 5);

        self::assertSame(1, $this->out->getParticipantCount());

        $result = $this->out->remove();

        self::assertNull((new EnrollmentMapper())->getEnrollmentById($enrollment->getId()), 'The participation goes with the sample program.');
        self::assertNull((new ProgramMapper())->getProgramById($starterId));
        self::assertNotNull((new ProgramMapper())->getProgramById($strengthId), 'A program with orders is never removed.');
        self::assertContains($strengthId, $this->out->getIds()['programs']);
        self::assertGreaterThan(0, $result['kept']);
    }

    public function testSampleContentIsComplete()
    {
        $data = require dirname(__DIR__, 4) . '/application/modules/fitness/config/sampledata.php';
        $imageDir = dirname(__DIR__, 4) . '/application/modules/fitness/static/sample/';

        foreach ($data['exercises'] as $key => $exercise) {
            self::assertFileExists($imageDir . $key . '.svg');
            self::assertArrayHasKey($exercise['category'], $data['categories'], $key);
            foreach (array_merge([$exercise['primary']], $exercise['muscles']) as $muscle) {
                self::assertArrayHasKey($muscle, $data['muscleGroups'], $key);
            }
            if ($exercise['video'] !== '') {
                self::assertNotNull(Media::videoEmbed($exercise['video']), $key);
            }
            foreach (['title', 'description', 'instructions', 'notes'] as $field) {
                self::assertSame(['de', 'en'], array_keys($exercise[$field]), $key . ' ' . $field);
            }
        }

        foreach ($data['workouts'] as $key => $workout) {
            foreach ($workout['items'] as $item) {
                self::assertArrayHasKey($item[0], $data['exercises'], $key);
            }
        }

        foreach ($data['programs'] as $key => $program) {
            self::assertFileExists($imageDir . $program['image'] . '.svg');
            foreach ($program['phases'] as $phase) {
                foreach ($phase['sessions'] as $session) {
                    self::assertArrayHasKey($session['workout'], $data['workouts'], $key);
                }
            }
        }
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
