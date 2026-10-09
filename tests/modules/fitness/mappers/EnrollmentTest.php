<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Models\ProgramPhase as ProgramPhaseModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\Fitness\Service\Access;
use Modules\User\Config\Config as UserConfig;
use Modules\User\Models\Group as GroupModel;
use Modules\User\Models\User as UserModel;
use PHPUnit\Ilch\DatabaseTestCase;

/**
 * Enrollments, done sessions and the access check.
 */
class EnrollmentTest extends DatabaseTestCase
{
    protected Enrollment $out;

    private int $programId;

    /**
     * @var int[]
     */
    private array $sessionIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Enrollment();

        foreach ([5 => 'Anna', 6 => 'Ben'] as $id => $name) {
            $this->db->insert('users')
                ->values(['id' => $id, 'name' => $name, 'password' => '', 'email' => $name . '@example.org', 'date_created' => '2026-10-07 10:00:00', 'confirmed' => 1, 'locale' => ''])
                ->execute();
        }

        $workoutId = (new Workout())->save((new WorkoutModel())->setTitle('Training A'));
        $this->programId = (new Program())->save((new ProgramModel())->setTitle('Programm')->setStatus(ProgramModel::STATUS_PUBLISHED));
        (new ProgramStructure())->syncStructure($this->programId, [
            (new ProgramPhaseModel())->setTitle('Woche 1')->setSessions([
                (new ProgramSessionModel())->setWorkoutId($workoutId),
                (new ProgramSessionModel())->setWorkoutId($workoutId),
            ]),
        ]);
        foreach ((new ProgramStructure())->getPhasesOfProgram($this->programId)[0]->getSessions() as $session) {
            $this->sessionIds[] = $session->getId();
        }
    }

    public function testEnrollCreatesActiveFreeEnrollmentOnlyOnce()
    {
        $first = $this->out->enroll($this->programId, 5);
        $second = $this->out->enroll($this->programId, 5);

        self::assertNotNull($first);
        self::assertSame($first->getId(), $second->getId());
        self::assertSame(EnrollmentModel::STATUS_ACTIVE, $first->getStatus());
        self::assertSame(EnrollmentModel::SOURCE_FREE, $first->getSource());
        self::assertSame('Programm', $first->getProgramTitle());
        self::assertSame('Anna', $first->getUserName());
        self::assertCount(1, $this->out->getEnrollmentsOfProgram($this->programId));
        self::assertSame([$this->programId => 1], $this->out->getCountsPerProgram());
    }

    public function testStatusChangeSetsAndClearsCompletionDate()
    {
        $enrollment = $this->out->enroll($this->programId, 5);

        $this->out->updateStatus($enrollment, EnrollmentModel::STATUS_COMPLETED);
        $stored = $this->out->getEnrollmentById($enrollment->getId());
        self::assertSame(EnrollmentModel::STATUS_COMPLETED, $stored->getStatus());
        self::assertNotNull($stored->getCompletedAt());

        $this->out->updateStatus($stored, EnrollmentModel::STATUS_ACTIVE);
        self::assertNull($this->out->getEnrollmentById($enrollment->getId())->getCompletedAt());
    }

    public function testSessionLogIsStoredOnceAndCanBeUndone()
    {
        $enrollment = $this->out->enroll($this->programId, 5);
        $logMapper = new SessionLog();

        self::assertTrue($logMapper->markDone($enrollment->getId(), $this->sessionIds[0]));
        self::assertFalse($logMapper->markDone($enrollment->getId(), $this->sessionIds[0]));
        self::assertSame([$this->sessionIds[0]], array_keys($logMapper->getDoneSessions($enrollment->getId())));

        self::assertTrue($logMapper->unmarkDone($enrollment->getId(), $this->sessionIds[0]));
        self::assertFalse($logMapper->unmarkDone($enrollment->getId(), $this->sessionIds[0]));
        self::assertSame([], $logMapper->getDoneSessions($enrollment->getId()));
    }

    public function testLogsOfOneParticipantDontCountForAnother()
    {
        $anna = $this->out->enroll($this->programId, 5);
        $ben = $this->out->enroll($this->programId, 6);
        (new SessionLog())->markDone($anna->getId(), $this->sessionIds[0]);

        self::assertCount(1, (new SessionLog())->getDoneSessions($anna->getId()));
        self::assertCount(0, (new SessionLog())->getDoneSessions($ben->getId()));
    }

    public function testDoneSessionsOfSeveralEnrollmentsAtOnce()
    {
        $anna = $this->out->enroll($this->programId, 5);
        $ben = $this->out->enroll($this->programId, 6);
        $logMapper = new SessionLog();
        $logMapper->markDone($anna->getId(), $this->sessionIds[0]);
        $logMapper->markDone($anna->getId(), $this->sessionIds[1]);

        $sessions = $logMapper->getDoneSessionsOfEnrollments([$anna->getId(), $ben->getId()]);

        self::assertSame($this->sessionIds, array_keys($sessions[$anna->getId()]));
        self::assertSame([], $sessions[$ben->getId()], 'Enrollments without logs are in the result as well.');
        self::assertSame([], $logMapper->getDoneSessionsOfEnrollments([]));
    }

    public function testAccessNeedsAnActiveOrCompletedEnrollment()
    {
        $access = new Access();
        $program = (new Program())->getProgramById($this->programId);
        $anna = $this->user(5, 2);
        $ben = $this->user(6, 2);

        self::assertFalse($access->canViewProgramContent(null, $program));
        self::assertFalse($access->canViewProgramContent($anna, $program));

        $enrollment = $this->out->enroll($this->programId, 5);
        self::assertTrue($access->canViewProgramContent($anna, $program));
        self::assertFalse($access->canViewProgramContent($ben, $program), 'An enrollment only opens the program for its own user.');

        $this->out->updateStatus($enrollment, EnrollmentModel::STATUS_COMPLETED);
        self::assertTrue($access->canViewProgramContent($anna, $program));

        foreach ([EnrollmentModel::STATUS_PAUSED, EnrollmentModel::STATUS_REVOKED] as $status) {
            $this->out->updateStatus($enrollment, $status);
            self::assertFalse($access->canViewProgramContent($anna, $program));
        }
    }

    public function testAdminsMayPreviewWithoutEnrollment()
    {
        $program = (new Program())->getProgramById($this->programId);

        self::assertTrue((new Access())->canViewProgramContent($this->user(6, 1), $program));
        self::assertTrue(Access::canManage($this->user(6, 1)));
        self::assertFalse(Access::canManage($this->user(6, 2)));
        self::assertFalse(Access::canManage(null));
    }

    public function testJoiningForFreeNeedsAPublishedFreeVisibleProgram()
    {
        $access = new Access();
        $anna = $this->user(5, 2);
        $program = (new Program())->getProgramById($this->programId);

        self::assertTrue($access->canJoinForFree($anna, $program, [2]));
        self::assertFalse($access->canJoinForFree(null, $program, [3]));
        self::assertFalse($access->canJoinForFree($anna, (clone $program)->setStatus(ProgramModel::STATUS_DRAFT), [2]));
        self::assertFalse($access->canJoinForFree($anna, (clone $program)->setAccessType(ProgramModel::ACCESS_PAID), [2]));
        self::assertFalse($access->canJoinForFree($anna, (clone $program)->setReadAccessAll(false)->setGroupIds([4]), [2]));
    }

    private function user(int $id, int $groupId): UserModel
    {
        $user = new UserModel();
        $user->setId($id);
        $user->addGroup((new GroupModel())->setId($groupId));

        return $user;
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
