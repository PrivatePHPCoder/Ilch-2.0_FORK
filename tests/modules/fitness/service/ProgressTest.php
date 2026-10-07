<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Models\ProgramPhase;
use Modules\Fitness\Models\ProgramSession;
use PHPUnit\Framework\TestCase;

class ProgressTest extends TestCase
{
    /**
     * Two weeks: week 1 with sessions 1, 2 and the optional session 3, week 2 with sessions 4 and 5.
     *
     * @return ProgramPhase[]
     */
    private function phases(): array
    {
        return [
            (new ProgramPhase())->setId(10)->setTitle('Woche 1')->setSessions([
                (new ProgramSession())->setId(1)->setPhaseId(10),
                (new ProgramSession())->setId(2)->setPhaseId(10),
                (new ProgramSession())->setId(3)->setPhaseId(10)->setOptional(true),
            ]),
            (new ProgramPhase())->setId(20)->setTitle('Woche 2')->setSessions([
                (new ProgramSession())->setId(4)->setPhaseId(20),
                (new ProgramSession())->setId(5)->setPhaseId(20),
            ]),
        ];
    }

    public function testNothingDone()
    {
        $progress = Progress::calculate($this->phases(), []);

        self::assertSame(4, $progress->getTotal());
        self::assertSame(0, $progress->getDone());
        self::assertSame(0, $progress->getPercent());
        self::assertFalse($progress->isComplete());
        self::assertSame(1, $progress->getNextSession()->getId());
        self::assertSame('Woche 1', $progress->getCurrentPhase()->getTitle());
    }

    public function testOptionalSessionsDontCountAndDontBlock()
    {
        $progress = Progress::calculate($this->phases(), [1, 3]);

        self::assertSame(1, $progress->getDone());
        self::assertSame(25, $progress->getPercent());
        self::assertTrue($progress->isSessionDone(3));
        self::assertSame(2, $progress->getNextSession()->getId());
    }

    public function testNextSessionMovesToNextWeekAndPercentIsRoundedDown()
    {
        $phases = $this->phases();
        $phases[1]->setSessions(array_merge($phases[1]->getSessions(), [(new ProgramSession())->setId(6)->setPhaseId(20)]));

        // 2 of 5 required sessions = 40 %, 3 of 5 = 60 %, 1 of 3 would be 33 %.
        $progress = Progress::calculate($phases, [1, 2]);
        self::assertSame(40, $progress->getPercent());
        self::assertSame(4, $progress->getNextSession()->getId());
        self::assertSame('Woche 2', $progress->getCurrentPhase()->getTitle());
        self::assertTrue($progress->isPhaseDone(10));
        self::assertFalse($progress->isPhaseDone(20));
        self::assertSame(1, $progress->getDonePhaseCount());

        $oneThird = Progress::calculate([(new ProgramPhase())->setId(1)->setSessions([
            (new ProgramSession())->setId(1),
            (new ProgramSession())->setId(2),
            (new ProgramSession())->setId(3),
        ])], [1]);
        self::assertSame(33, $oneThird->getPercent());
    }

    public function testDoneOutOfOrderUsesFirstOpenSession()
    {
        $progress = Progress::calculate($this->phases(), [4, 5]);

        self::assertSame(1, $progress->getNextSession()->getId());
        self::assertTrue($progress->isPhaseDone(20));
        self::assertFalse($progress->isPhaseDone(10));
    }

    public function testEverythingDone()
    {
        $progress = Progress::calculate($this->phases(), [1, 2, 4, 5]);

        self::assertSame(100, $progress->getPercent());
        self::assertTrue($progress->isComplete());
        self::assertNull($progress->getNextSession());
        self::assertSame('Woche 2', $progress->getCurrentPhase()->getTitle());
    }

    public function testUnknownSessionIdsAreIgnored()
    {
        $progress = Progress::calculate($this->phases(), [99]);

        self::assertSame(0, $progress->getDone());
        self::assertFalse($progress->isSessionDone(99));
    }

    public function testEmptyProgramIsNeverComplete()
    {
        $progress = Progress::calculate([], []);

        self::assertSame(0, $progress->getPercent());
        self::assertFalse($progress->isComplete());
        self::assertNull($progress->getNextSession());
        self::assertNull($progress->getCurrentPhase());
    }
}
