<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Milestone as MilestoneModel;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Models\ProgramPhase as ProgramPhaseModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\Fitness\Service\Milestones;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

/**
 * Storing milestones and giving them to users.
 */
class MilestoneTest extends DatabaseTestCase
{
    protected Milestone $out;

    private int $programId;

    private int $otherProgramId;

    /**
     * Sessions of the program: week 1 with two sessions, week 2 with two sessions.
     *
     * @var int[]
     */
    private array $sessionIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Milestone();

        foreach ([5 => 'Anna', 6 => 'Ben'] as $id => $name) {
            $this->db->insert('users')
                ->values(['id' => $id, 'name' => $name, 'password' => '', 'email' => $name . '@example.org', 'date_created' => '2026-10-08 10:00:00', 'confirmed' => 1, 'locale' => ''])
                ->execute();
        }

        $workoutId = (new Workout())->save((new WorkoutModel())->setTitle('Training A'));
        $this->programId = (new Program())->save((new ProgramModel())->setTitle('Programm')->setStatus(ProgramModel::STATUS_PUBLISHED));
        $this->otherProgramId = (new Program())->save((new ProgramModel())->setTitle('Anderes Programm')->setStatus(ProgramModel::STATUS_PUBLISHED));

        $structureMapper = new ProgramStructure();
        $structureMapper->syncStructure($this->programId, [
            (new ProgramPhaseModel())->setTitle('Woche 1')->setSessions([
                (new ProgramSessionModel())->setWorkoutId($workoutId),
                (new ProgramSessionModel())->setWorkoutId($workoutId),
            ]),
            (new ProgramPhaseModel())->setTitle('Woche 2')->setSessions([
                (new ProgramSessionModel())->setWorkoutId($workoutId),
                (new ProgramSessionModel())->setWorkoutId($workoutId),
            ]),
        ]);
        foreach ($structureMapper->getPhasesOfProgram($this->programId) as $phase) {
            foreach ($phase->getSessions() as $session) {
                $this->sessionIds[] = $session->getId();
            }
        }
    }

    public function testDefaultsAreCreatedOnlyWhenThereAreNoMilestones()
    {
        self::assertSame(count(Milestone::DEFAULTS), $this->out->createDefaults());
        self::assertSame(0, $this->out->createDefaults());

        $milestones = $this->out->getMilestones();
        self::assertCount(count(Milestone::DEFAULTS), $milestones);
        self::assertSame('', $milestones[0]->getTitle(), 'Defaults have no own title, so it is shown in the language of the visitor.');
        self::assertSame(range(1, count(Milestone::DEFAULTS)), array_map(static fn ($milestone) => $milestone->getPosition(), $milestones));
    }

    public function testProgramCanBeRemovedFromMilestone()
    {
        $id = $this->out->save((new MilestoneModel())->setType(MilestoneModel::TYPE_PERCENT)->setThreshold(50)->setProgramId($this->programId)->setTitle('Halbzeit'));

        $stored = $this->out->getMilestoneById($id);
        self::assertSame($this->programId, $stored->getProgramId());
        self::assertSame('Programm', $stored->getProgramTitle());

        $this->out->save($stored->setProgramId(null));
        self::assertTrue($this->out->getMilestoneById($id)->isForAllPrograms());
    }

    public function testDeletingProgramDeletesItsMilestones()
    {
        $id = $this->out->save((new MilestoneModel())->setProgramId($this->otherProgramId));

        (new Program())->delete($this->otherProgramId);

        self::assertNull($this->out->getMilestoneById($id));
    }

    public function testMilestoneIsAwardedOnlyOnce()
    {
        $id = $this->out->save(new MilestoneModel());

        self::assertTrue($this->out->award($id, 5));
        self::assertFalse($this->out->award($id, 5));
        self::assertTrue($this->out->award($id, 6));

        self::assertSame([$id], array_keys($this->out->getAchievementsOfUser(5)));
        self::assertSame(2, $this->out->getMilestoneById($id)->getAchievedCount());
    }

    public function testEvaluateAwardsReachedMilestonesOnce()
    {
        $first = $this->out->save((new MilestoneModel())->setType(MilestoneModel::TYPE_SESSIONS)->setThreshold(1));
        $week = $this->out->save((new MilestoneModel())->setType(MilestoneModel::TYPE_PHASES)->setThreshold(1));
        $halfway = $this->out->save((new MilestoneModel())->setType(MilestoneModel::TYPE_PERCENT)->setThreshold(50)->setProgramId($this->programId));
        $done = $this->out->save((new MilestoneModel())->setType(MilestoneModel::TYPE_PROGRAMS)->setThreshold(1)->setProgramId($this->programId));
        $service = new Milestones();
        $enrollment = (new Enrollment())->enroll($this->programId, 5);
        $logMapper = new SessionLog();

        self::assertSame([], $service->evaluate(5, $enrollment->getId()), 'Nothing done yet.');

        $logMapper->markDone($enrollment->getId(), $this->sessionIds[0]);
        self::assertSame([$first], $this->ids($service->evaluate(5, $enrollment->getId())));
        self::assertSame([], $service->evaluate(5, $enrollment->getId()), 'A milestone is given only once.');

        $logMapper->markDone($enrollment->getId(), $this->sessionIds[1]);
        self::assertSame([$week, $halfway], $this->ids($service->evaluate(5, $enrollment->getId())));

        $logMapper->markDone($enrollment->getId(), $this->sessionIds[2]);
        $logMapper->markDone($enrollment->getId(), $this->sessionIds[3]);
        self::assertSame([$done], $this->ids($service->evaluate(5, $enrollment->getId())));

        self::assertSame([$done, $halfway, $week, $first], array_keys($this->out->getAchievementsOfUser(5)), 'Newest first.');
        self::assertSame([], $service->evaluate(6), 'Ben has done nothing.');
    }

    public function testReachedMilestonesStayWhenSessionIsUndone()
    {
        $first = $this->out->save(new MilestoneModel());
        $enrollment = (new Enrollment())->enroll($this->programId, 5);
        $logMapper = new SessionLog();

        $logMapper->markDone($enrollment->getId(), $this->sessionIds[0]);
        (new Milestones())->evaluate(5, $enrollment->getId());
        $logMapper->unmarkDone($enrollment->getId(), $this->sessionIds[0]);

        self::assertSame([], (new Milestones())->evaluate(5, $enrollment->getId()));
        self::assertArrayHasKey($first, $this->out->getAchievementsOfUser(5));
    }

    public function testInactiveMilestonesAndOtherProgramsAreIgnored()
    {
        $this->out->save((new MilestoneModel())->setActive(false));
        $this->out->save((new MilestoneModel())->setProgramId($this->otherProgramId));
        $enrollment = (new Enrollment())->enroll($this->programId, 5);
        (new SessionLog())->markDone($enrollment->getId(), $this->sessionIds[0]);

        self::assertSame([], (new Milestones())->evaluate(5, $enrollment->getId()));
    }

    public function testAchievementIsLinkedToTheParticipation()
    {
        $global = $this->out->save(new MilestoneModel());
        $ofProgram = $this->out->save((new MilestoneModel())->setProgramId($this->programId));
        $enrollment = (new Enrollment())->enroll($this->programId, 5);
        (new SessionLog())->markDone($enrollment->getId(), $this->sessionIds[0]);

        (new Milestones())->evaluate(5);

        $links = [];
        foreach ($this->db->select(['milestone_id', 'enrollment_id'])->from('fitness_user_milestones')->execute()->fetchRows() as $row) {
            $links[(int)$row['milestone_id']] = $row['enrollment_id'] === null ? null : (int)$row['enrollment_id'];
        }
        self::assertNull($links[$global], 'Without a cause the milestone for all programs has no participation.');
        self::assertSame($enrollment->getId(), $links[$ofProgram]);
    }

    /**
     * @param MilestoneModel[] $milestones
     * @return int[]
     */
    private function ids(array $milestones): array
    {
        return array_map(static fn ($milestone) => $milestone->getId(), $milestones);
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
