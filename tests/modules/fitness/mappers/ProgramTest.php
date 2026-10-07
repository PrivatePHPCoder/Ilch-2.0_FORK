<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Models\ProgramPhase as ProgramPhaseModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

class ProgramTest extends DatabaseTestCase
{
    protected Program $out;

    protected ProgramStructure $structure;

    /**
     * @var int[] workout title => id
     */
    private array $workoutIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Program();
        $this->structure = new ProgramStructure();

        $workoutMapper = new Workout();
        foreach (['Training A', 'Training B'] as $title) {
            $this->workoutIds[$title] = $workoutMapper->save((new WorkoutModel())->setTitle($title));
        }
    }

    public function testSaveProgram()
    {
        $id = $this->out->save((new ProgramModel())
            ->setTitle('8 Wochen Muskelaufbau')
            ->setTeaser('Kurz')
            ->setGoal('Muskelaufbau')
            ->setStatus(ProgramModel::STATUS_PUBLISHED)
            ->setAccessType(ProgramModel::ACCESS_PAID)
            ->setPrice('29,9')
            ->setCurrency('eur'));

        $program = $this->out->getProgramById($id);
        self::assertSame('8 Wochen Muskelaufbau', $program->getTitle());
        self::assertSame('Muskelaufbau', $program->getGoal());
        self::assertTrue($program->isPublished());
        self::assertTrue($program->isPaid());
        self::assertSame('29.90', $program->getPrice());
        self::assertSame('EUR', $program->getCurrency());
        self::assertTrue($program->isReadAccessAll());
        self::assertSame([], $program->getGroupIds());
    }

    public function testGroupAccessIsStoredAndReplaced()
    {
        $id = $this->out->save((new ProgramModel())->setTitle('Nur Mitglieder')->setReadAccessAll(false)->setGroupIds([2]));
        self::assertSame([2], $this->out->getProgramById($id)->getGroupIds());

        $program = $this->out->getProgramById($id);
        $program->setGroupIds([2, 3]);
        $this->out->save($program);
        self::assertEqualsCanonicalizing([2, 3], $this->out->getProgramById($id)->getGroupIds());

        $program->setReadAccessAll(true);
        $this->out->save($program);
        self::assertSame([], $this->out->getProgramById($id)->getGroupIds());
    }

    public function testPublishedProgramsForGroups()
    {
        $this->out->save((new ProgramModel())->setTitle('Entwurf'));
        $this->out->save((new ProgramModel())->setTitle('Für alle')->setStatus(ProgramModel::STATUS_PUBLISHED));
        $this->out->save((new ProgramModel())->setTitle('Mitglieder')->setStatus(ProgramModel::STATUS_PUBLISHED)->setReadAccessAll(false)->setGroupIds([2]));
        $this->out->save((new ProgramModel())->setTitle('Archiv')->setStatus(ProgramModel::STATUS_ARCHIVED));

        $titlesForGuests = array_map(static fn ($program) => $program->getTitle(), $this->out->getPublishedProgramsForGroups([3]));
        self::assertSame(['Für alle'], $titlesForGuests);

        $titlesForMembers = array_map(static fn ($program) => $program->getTitle(), $this->out->getPublishedProgramsForGroups([2]));
        self::assertSame(['Für alle', 'Mitglieder'], $titlesForMembers);
    }

    public function testModelNormalizesValues()
    {
        $program = (new ProgramModel())->setStatus(9)->setAccessType(7)->setPrice('abc')->setCurrency('Euro')->setType('daily');

        self::assertSame(ProgramModel::STATUS_DRAFT, $program->getStatus());
        self::assertSame(ProgramModel::ACCESS_FREE, $program->getAccessType());
        self::assertSame('0.00', $program->getPrice());
        self::assertSame('EUR', $program->getCurrency());
        self::assertSame('weekly', $program->getType());
    }

    public function testStructureIsSavedInOrderAndCounted()
    {
        $id = $this->out->save((new ProgramModel())->setTitle('Programm'));
        $this->structure->syncStructure($id, [
            $this->phase('Woche 1', ['Training A', 'Training B']),
            $this->phase('Woche 2', ['Training A']),
        ]);

        $phases = $this->structure->getPhasesOfProgram($id);
        self::assertSame(['Woche 1', 'Woche 2'], array_map(static fn ($phase) => $phase->getTitle(), $phases));
        self::assertSame(['Training A', 'Training B'], array_map(static fn ($session) => $session->getWorkoutTitle(), $phases[0]->getSessions()));
        self::assertSame(1, $phases[0]->getSessions()[0]->getDayHint());

        $program = $this->out->getProgramById($id);
        self::assertSame(2, $program->getPhaseCount());
        self::assertSame(3, $program->getSessionCount());
    }

    /**
     * Sessions that stay keep their id, also when they move to another week.
     * Participants' progress refers to these ids.
     */
    public function testSyncKeepsIdsAndMovesSessions()
    {
        $id = $this->out->save((new ProgramModel())->setTitle('Programm'));
        $this->structure->syncStructure($id, [
            $this->phase('Woche 1', ['Training A', 'Training B']),
            $this->phase('Woche 2', ['Training A']),
        ]);
        [$week1, $week2] = $this->structure->getPhasesOfProgram($id);
        [$sessionA, $sessionB] = $week1->getSessions();

        // Week 2 comes first now, session B moves into it, week 1 keeps session A, a new week 3 is added.
        $week2->setTitle('Woche 2 neu')->setSessions(array_merge($week2->getSessions(), [$sessionB]));
        $week1->setSessions([$sessionA->setTitle('A neu')->setDayHint(null)]);
        $this->structure->syncStructure($id, [$week2, $week1, $this->phase('Woche 3', ['Training B'])]);

        $phases = $this->structure->getPhasesOfProgram($id);
        self::assertSame(['Woche 2 neu', 'Woche 1', 'Woche 3'], array_map(static fn ($phase) => $phase->getTitle(), $phases));
        self::assertSame($week2->getId(), $phases[0]->getId());
        self::assertSame($sessionB->getId(), $phases[0]->getSessions()[1]->getId());
        self::assertSame($sessionA->getId(), $phases[1]->getSessions()[0]->getId());
        self::assertSame('A neu', $phases[1]->getSessions()[0]->getTitle());
        self::assertNull($phases[1]->getSessions()[0]->getDayHint());
        self::assertSame(4, $this->out->getProgramById($id)->getSessionCount());
    }

    public function testSyncRemovesPhasesAndSessions()
    {
        $id = $this->out->save((new ProgramModel())->setTitle('Programm'));
        $this->structure->syncStructure($id, [
            $this->phase('Woche 1', ['Training A', 'Training B']),
            $this->phase('Woche 2', ['Training A']),
        ]);
        [$week1] = $this->structure->getPhasesOfProgram($id);

        $this->structure->syncStructure($id, [$week1->setSessions([$week1->getSessions()[0]])]);

        $program = $this->out->getProgramById($id);
        self::assertSame(1, $program->getPhaseCount());
        self::assertSame(1, $program->getSessionCount());
    }

    public function testWorkoutInProgramIsProtected()
    {
        $id = $this->out->save((new ProgramModel())->setTitle('Programm'));
        $this->structure->syncStructure($id, [$this->phase('Woche 1', ['Training A'])]);

        $workoutMapper = new Workout();
        self::assertFalse($workoutMapper->delete($this->workoutIds['Training A']));
        self::assertTrue($workoutMapper->delete($this->workoutIds['Training B']));
    }

    public function testDeleteRemovesStructureButNotWithParticipants()
    {
        $id = $this->out->save((new ProgramModel())->setTitle('Programm'));
        $this->structure->syncStructure($id, [$this->phase('Woche 1', ['Training A'])]);

        $otherId = $this->out->save((new ProgramModel())->setTitle('Mit Teilnehmer'));
        $this->db->insert('users')
            ->values(['id' => 5, 'name' => 'Tester', 'password' => '', 'email' => 'tester@example.org', 'date_created' => '2026-10-07 10:00:00', 'date_confirmed' => '2026-10-07 10:00:00', 'confirmed' => 1, 'locale' => ''])
            ->execute();
        $this->db->insert('fitness_enrollments')
            ->values(['program_id' => $otherId, 'user_id' => 5, 'started_at' => '2026-10-07 10:00:00'])
            ->execute();

        self::assertTrue($this->out->delete($id));
        self::assertNull($this->out->getProgramById($id));
        self::assertSame([], $this->structure->getPhasesOfProgram($id));

        self::assertTrue($this->out->hasParticipantsOrOrders($otherId));
        self::assertFalse($this->out->delete($otherId));
        self::assertNotNull($this->out->getProgramById($otherId));
    }

    /**
     * @param string $title
     * @param string[] $workoutTitles
     * @return ProgramPhaseModel
     */
    private function phase(string $title, array $workoutTitles): ProgramPhaseModel
    {
        $sessions = [];
        foreach ($workoutTitles as $workoutTitle) {
            $sessions[] = (new ProgramSessionModel())
                ->setWorkoutId($this->workoutIds[$workoutTitle])
                ->setDayHint(1);
        }

        return (new ProgramPhaseModel())->setTitle($title)->setSessions($sessions);
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
