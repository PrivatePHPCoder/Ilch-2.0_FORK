<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Models\Milestone;
use PHPUnit\Framework\TestCase;

/**
 * The rules that decide when a milestone is reached. No database needed.
 */
class MilestonesTest extends TestCase
{
    /**
     * Program 1: 6 sessions, 2 weeks, 50 %. Program 2: 4 sessions, 1 week, completed.
     *
     * @return array
     */
    private function stats(): array
    {
        return [
            1 => ['enrollmentId' => 11, 'sessions' => 6, 'phases' => 2, 'percent' => 50, 'complete' => false],
            2 => ['enrollmentId' => 12, 'sessions' => 4, 'phases' => 1, 'percent' => 100, 'complete' => true],
        ];
    }

    private function milestone(string $type, int $threshold, ?int $programId = null): Milestone
    {
        return (new Milestone())->setType($type)->setThreshold($threshold)->setProgramId($programId);
    }

    public function testMilestonesForAllProgramsAddUpAllPrograms()
    {
        self::assertSame(10, Milestones::getCurrentValue($this->milestone(Milestone::TYPE_SESSIONS, 10), $this->stats()));
        self::assertTrue(Milestones::isReached($this->milestone(Milestone::TYPE_SESSIONS, 10), $this->stats()));
        self::assertFalse(Milestones::isReached($this->milestone(Milestone::TYPE_SESSIONS, 11), $this->stats()));

        self::assertSame(3, Milestones::getCurrentValue($this->milestone(Milestone::TYPE_PHASES, 3), $this->stats()));
        self::assertSame(1, Milestones::getCurrentValue($this->milestone(Milestone::TYPE_PROGRAMS, 1), $this->stats()));
    }

    public function testPercentUsesTheBestProgram()
    {
        self::assertSame(100, Milestones::getCurrentValue($this->milestone(Milestone::TYPE_PERCENT, 50), $this->stats()));
        self::assertTrue(Milestones::isReached($this->milestone(Milestone::TYPE_PERCENT, 100), $this->stats()));
    }

    public function testMilestonesOfOneProgramOnlyCountThatProgram()
    {
        self::assertSame(6, Milestones::getCurrentValue($this->milestone(Milestone::TYPE_SESSIONS, 10, 1), $this->stats()));
        self::assertFalse(Milestones::isReached($this->milestone(Milestone::TYPE_SESSIONS, 10, 1), $this->stats()));
        self::assertTrue(Milestones::isReached($this->milestone(Milestone::TYPE_PERCENT, 50, 1), $this->stats()));
        self::assertFalse(Milestones::isReached($this->milestone(Milestone::TYPE_PERCENT, 51, 1), $this->stats()));
        self::assertFalse(Milestones::isReached($this->milestone(Milestone::TYPE_PROGRAMS, 1, 1), $this->stats()));
        self::assertTrue(Milestones::isReached($this->milestone(Milestone::TYPE_PROGRAMS, 1, 2), $this->stats()));
    }

    public function testNothingIsReachedWithoutParticipation()
    {
        foreach (array_keys(Milestone::TYPES) as $type) {
            self::assertSame(0, Milestones::getCurrentValue($this->milestone($type, 1), []), $type);
            self::assertFalse(Milestones::isReached($this->milestone($type, 1), []), $type);
        }

        self::assertFalse(Milestones::isReached($this->milestone(Milestone::TYPE_SESSIONS, 1, 3), $this->stats()), 'Program 3 has no participation.');
    }

    public function testThresholdBelowOneIsNotReachedForFree()
    {
        self::assertFalse(Milestones::isReached($this->milestone(Milestone::TYPE_SESSIONS, 0), []));
    }

    public function testOnlyMilestonesOfOwnProgramsAreRelevant()
    {
        $milestones = [
            $this->milestone(Milestone::TYPE_SESSIONS, 1)->setId(1),
            $this->milestone(Milestone::TYPE_SESSIONS, 1, 1)->setId(2),
            $this->milestone(Milestone::TYPE_SESSIONS, 1, 3)->setId(3),
        ];

        $relevant = Milestones::getRelevantMilestones($milestones, $this->stats());

        self::assertSame([1, 2], array_map(static fn ($milestone) => $milestone->getId(), $relevant));
    }

    public function testTotals()
    {
        self::assertSame(['sessions' => 10, 'phases' => 3, 'programs' => 1], Milestones::getTotals($this->stats()));
        self::assertSame(['sessions' => 0, 'phases' => 0, 'programs' => 0], Milestones::getTotals([]));
    }

    public function testAutomaticTitles()
    {
        self::assertSame('milestoneAutoFirstSession', $this->milestone(Milestone::TYPE_SESSIONS, 1)->getAutoTitleKey());
        self::assertSame('milestoneAutoSessions', $this->milestone(Milestone::TYPE_SESSIONS, 5)->getAutoTitleKey());
        self::assertSame('milestoneAutoHalfway', $this->milestone(Milestone::TYPE_PERCENT, 50)->getAutoTitleKey());
        self::assertSame('milestoneAutoProgramDone', $this->milestone(Milestone::TYPE_PROGRAMS, 1, 2)->getAutoTitleKey());
        self::assertSame('milestoneAutoFirstProgram', $this->milestone(Milestone::TYPE_PROGRAMS, 1)->getAutoTitleKey());
    }

    public function testUnknownTypeAndIconFallBack()
    {
        $milestone = (new Milestone())->setType('unknown')->setIcon('fa-solid fa-skull" onclick="alert(1)');

        self::assertSame(Milestone::TYPE_SESSIONS, $milestone->getType());
        self::assertSame(Milestone::ICONS[0], $milestone->getIcon());
        self::assertTrue((new Milestone())->setProgramId(0)->isForAllPrograms());
    }
}
