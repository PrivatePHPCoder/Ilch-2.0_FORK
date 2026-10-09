<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Config;

use Ilch\Config\Database as DatabaseConfig;
use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Mappers\Milestone as MilestoneMapper;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

class ConfigTest extends DatabaseTestCase
{
    protected Config $out;

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Config();
    }

    /**
     * Tests that the install SQL creates every table of the module.
     */
    public function testInstallSqlCreatesAllTables()
    {
        foreach ($this->out->getTables() as $table) {
            self::assertTrue($this->db->ifTableExists($table), 'Table "' . $table . '" is missing.');
        }
    }

    /**
     * Tests that install() stores the default settings.
     */
    public function testInstallSetsDefaultSettings()
    {
        $this->out->install();

        $databaseConfig = new DatabaseConfig($this->db);
        self::assertSame('1', $databaseConfig->get('fitness_ownLayout', true));
    }

    /**
     * Tests that install() creates the default milestones.
     */
    public function testInstallCreatesDefaultMilestones()
    {
        $this->out->install();

        self::assertSame(count(MilestoneMapper::DEFAULTS), $this->countRows('fitness_milestones', []));
    }

    /**
     * Tests that the update from 1.0.0 adds the default milestones and registers the box, and that
     * running it twice changes nothing.
     */
    public function testUpdateFrom100AddsMilestonesAndBoxOnce()
    {
        $this->db->insert('modules')
            ->values(['key' => 'fitness', 'version' => '1.0.0', 'icon_small' => 'fa-solid fa-dumbbell'])
            ->execute();

        $this->out->getUpdate('1.0.0');
        $this->out->getUpdate('1.0.0');

        self::assertSame(count(MilestoneMapper::DEFAULTS), $this->countRows('fitness_milestones', []));
        self::assertSame(2, $this->countRows('modules_boxes_content', ['module' => 'fitness', 'key' => 'progress']));
    }

    /**
     * Tests that uninstall() removes all tables and settings of the module.
     */
    public function testUninstallRemovesTablesAndSettings()
    {
        $this->out->install();
        $this->out->uninstall();

        foreach ($this->out->getTables() as $table) {
            self::assertFalse($this->db->ifTableExists($table), 'Table "' . $table . '" still exists.');
        }

        $databaseConfig = new DatabaseConfig($this->db);
        self::assertNull($databaseConfig->get('fitness_ownLayout', true));
    }

    /**
     * Tests that an exercise can't be deleted while a workout still uses it.
     * The exercise is stored once and only referenced by workouts.
     */
    public function testExerciseInUseCannotBeDeleted()
    {
        $exerciseId = $this->insertExercise();
        $workoutId = $this->insertWorkout();
        $this->db->insert('fitness_workout_exercises')
            ->values(['workout_id' => $workoutId, 'exercise_id' => $exerciseId, 'notes' => ''])
            ->execute();

        try {
            $this->db->delete('fitness_exercises')->where(['id' => $exerciseId])->execute();
        } catch (\Exception $exception) {
            // Expected: the foreign key blocks the delete.
        }

        self::assertSame(1, $this->countRows('fitness_exercises', ['id' => $exerciseId]));
    }

    /**
     * Tests that deleting a program also deletes its phases and sessions.
     */
    public function testDeletingProgramDeletesPhasesAndSessions()
    {
        $workoutId = $this->insertWorkout();
        $programId = $this->db->insert('fitness_programs')
            ->values(['title' => 'Programm', 'description' => '', 'created_at' => '2026-10-07 10:00:00'])
            ->execute();
        $phaseId = $this->db->insert('fitness_program_phases')
            ->values(['program_id' => $programId, 'title' => 'Woche 1', 'description' => ''])
            ->execute();
        $this->db->insert('fitness_program_sessions')
            ->values(['program_id' => $programId, 'phase_id' => $phaseId, 'workout_id' => $workoutId, 'title' => 'Training A'])
            ->execute();

        $this->db->delete('fitness_programs')->where(['id' => $programId])->execute();

        self::assertSame(0, $this->countRows('fitness_program_phases', ['program_id' => $programId]));
        self::assertSame(0, $this->countRows('fitness_program_sessions', ['program_id' => $programId]));
        self::assertSame(1, $this->countRows('fitness_workouts', ['id' => $workoutId]));
    }

    /**
     * Tests that deleting a category keeps its exercises and only removes the assignment.
     */
    public function testDeletingCategoryKeepsExercises()
    {
        $categoryId = $this->db->insert('fitness_categories')
            ->values(['name' => 'Kraft'])
            ->execute();
        $exerciseId = $this->insertExercise($categoryId);

        $this->db->delete('fitness_categories')->where(['id' => $categoryId])->execute();

        // fetchCell() returns false for NULL, so read the whole row.
        $exercise = $this->db->select(['id', 'category_id'])
            ->from('fitness_exercises')
            ->where(['id' => $exerciseId])
            ->execute()
            ->fetchAssoc();
        self::assertNotNull($exercise);
        self::assertNull($exercise['category_id']);
    }

    private function insertExercise(?int $categoryId = null): int
    {
        return $this->db->insert('fitness_exercises')
            ->values([
                'category_id' => $categoryId,
                'title' => 'Kniebeuge',
                'description' => '',
                'instructions' => '',
                'notes' => '',
                'created_at' => '2026-10-07 10:00:00',
            ])
            ->execute();
    }

    private function insertWorkout(): int
    {
        return $this->db->insert('fitness_workouts')
            ->values(['title' => 'Training A', 'description' => '', 'created_at' => '2026-10-07 10:00:00'])
            ->execute();
    }

    private function countRows(string $table, array $where): int
    {
        return (int)$this->db->select('COUNT(*)', $table, $where)
            ->execute()
            ->fetchCell();
    }

    /**
     * Returns database schema sql statements to initialize database
     *
     * @return string
     */
    protected static function getSchemaSQLQueries(): string
    {
        $config = new Config();
        $userConfig = new UserConfig();
        $adminConfig = new AdminConfig();

        return $adminConfig->getInstallSql() . $userConfig->getInstallSql() . $config->getInstallSql();
    }
}
