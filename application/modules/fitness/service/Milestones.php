<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Milestone as MilestoneMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Models\Milestone as MilestoneModel;
use Modules\Fitness\Models\Progress as ProgressModel;

/**
 * Decides which milestones a user has reached and stores new ones.
 *
 * Everything is measured against the logged sessions, like the progress. A milestone that
 * was reached once stays reached, even if a session is marked as open again later.
 */
class Milestones
{
    /**
     * @var MilestoneMapper
     */
    private MilestoneMapper $milestoneMapper;

    /**
     * @var EnrollmentMapper
     */
    private EnrollmentMapper $enrollmentMapper;

    /**
     * @var SessionLogMapper
     */
    private SessionLogMapper $logMapper;

    /**
     * @var ProgramStructureMapper
     */
    private ProgramStructureMapper $structureMapper;

    public function __construct(
        ?MilestoneMapper $milestoneMapper = null,
        ?EnrollmentMapper $enrollmentMapper = null,
        ?SessionLogMapper $logMapper = null,
        ?ProgramStructureMapper $structureMapper = null
    ) {
        $this->milestoneMapper = $milestoneMapper ?? new MilestoneMapper();
        $this->enrollmentMapper = $enrollmentMapper ?? new EnrollmentMapper();
        $this->logMapper = $logMapper ?? new SessionLogMapper();
        $this->structureMapper = $structureMapper ?? new ProgramStructureMapper();
    }

    /**
     * Collects the numbers the milestones are measured against, one entry per program the
     * user takes or took part in.
     *
     * @param int $userId
     * @return array<int, array{enrollmentId: int, sessions: int, phases: int, percent: int, complete: bool}> program id => numbers
     */
    public function getStatsOfUser(int $userId): array
    {
        $stats = [];
        foreach ($this->enrollmentMapper->getEnrollmentsOfUser($userId) as $enrollment) {
            $progress = Progress::calculate(
                $this->structureMapper->getPhasesOfProgram($enrollment->getProgramId()),
                array_keys($this->logMapper->getDoneSessions($enrollment->getId()))
            );
            $stats[$enrollment->getProgramId()] = self::getStatsEntry($enrollment, $progress);
        }

        return $stats;
    }

    /**
     * Same as getStatsOfUser(), but from already loaded trainings.
     *
     * @param array $trainings result of Trainings::getOfUser()
     * @return array<int, array{enrollmentId: int, sessions: int, phases: int, percent: int, complete: bool}>
     */
    public static function getStatsOfTrainings(array $trainings): array
    {
        $stats = [];
        foreach ($trainings as $training) {
            $stats[$training['enrollment']->getProgramId()] = self::getStatsEntry($training['enrollment'], $training['progress']);
        }

        return $stats;
    }

    /**
     * Adds up the numbers of all programs.
     *
     * @param array $stats result of getStatsOfUser()
     * @return array{sessions: int, phases: int, programs: int}
     */
    public static function getTotals(array $stats): array
    {
        return [
            'sessions' => (int)array_sum(array_column($stats, 'sessions')),
            'phases' => (int)array_sum(array_column($stats, 'phases')),
            'programs' => count(array_filter(array_column($stats, 'complete'))),
        ];
    }

    /**
     * @param EnrollmentModel $enrollment
     * @param ProgressModel $progress
     * @return array{enrollmentId: int, sessions: int, phases: int, percent: int, complete: bool}
     */
    private static function getStatsEntry(EnrollmentModel $enrollment, ProgressModel $progress): array
    {
        return [
            'enrollmentId' => $enrollment->getId(),
            'sessions' => $progress->getDoneSessionCount(),
            'phases' => $progress->getDonePhaseCount(),
            'percent' => $progress->getPercent(),
            'complete' => $progress->isComplete(),
        ];
    }

    /**
     * Returns how far the user is on the way to a milestone, in the unit of its type
     * (sessions, phases, percent or programs).
     *
     * @param MilestoneModel $milestone
     * @param array $stats result of getStatsOfUser()
     * @return int
     */
    public static function getCurrentValue(MilestoneModel $milestone, array $stats): int
    {
        if (!$milestone->isForAllPrograms()) {
            $stats = isset($stats[$milestone->getProgramId()]) ? [$stats[$milestone->getProgramId()]] : [];
        }

        switch ($milestone->getType()) {
            case MilestoneModel::TYPE_PHASES:
                return (int)array_sum(array_column($stats, 'phases'));
            case MilestoneModel::TYPE_PERCENT:
                return $stats ? (int)max(array_column($stats, 'percent')) : 0;
            case MilestoneModel::TYPE_PROGRAMS:
                return count(array_filter(array_column($stats, 'complete')));
            default:
                return (int)array_sum(array_column($stats, 'sessions'));
        }
    }

    /**
     * @param MilestoneModel $milestone
     * @param array $stats result of getStatsOfUser()
     * @return bool
     */
    public static function isReached(MilestoneModel $milestone, array $stats): bool
    {
        return self::getCurrentValue($milestone, $stats) >= max(1, $milestone->getThreshold());
    }

    /**
     * Keeps the milestones that concern the user: those for all programs and those of
     * programs the user takes part in.
     *
     * @param MilestoneModel[] $milestones
     * @param array $stats result of getStatsOfUser()
     * @return MilestoneModel[]
     */
    public static function getRelevantMilestones(array $milestones, array $stats): array
    {
        return array_values(array_filter(
            $milestones,
            static fn (MilestoneModel $milestone) => $milestone->isForAllPrograms() || isset($stats[$milestone->getProgramId()])
        ));
    }

    /**
     * Stores all active milestones the user has reached but not received yet.
     *
     * @param int $userId
     * @param int|null $enrollmentId participation that caused the check, linked to milestones for all programs
     * @param array|null $stats result of getStatsOfUser(), if already known
     * @return MilestoneModel[] milestones reached just now
     */
    public function evaluate(int $userId, ?int $enrollmentId = null, ?array $stats = null): array
    {
        $stats = $stats ?? $this->getStatsOfUser($userId);
        $achieved = $this->milestoneMapper->getAchievementsOfUser($userId);

        $reached = [];
        foreach (self::getRelevantMilestones($this->milestoneMapper->getActiveMilestones(), $stats) as $milestone) {
            if (isset($achieved[$milestone->getId()]) || !self::isReached($milestone, $stats)) {
                continue;
            }

            $linkedEnrollment = $milestone->isForAllPrograms() ? $enrollmentId : $stats[$milestone->getProgramId()]['enrollmentId'];
            if ($this->milestoneMapper->award($milestone->getId(), $userId, $linkedEnrollment)) {
                $reached[] = $milestone;
            }
        }

        return $reached;
    }
}
