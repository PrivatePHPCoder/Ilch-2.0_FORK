<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

/**
 * Progress of a participant in a program. Always calculated from the logged sessions,
 * never stored.
 */
class Progress
{
    /**
     * Number of required (not optional) sessions.
     *
     * @var int
     */
    private int $total;

    /**
     * Number of required sessions that are done.
     *
     * @var int
     */
    private int $done;

    /**
     * @var ProgramSession|null
     */
    private ?ProgramSession $nextSession;

    /**
     * Phase of the next session, or the last phase when everything is done.
     *
     * @var ProgramPhase|null
     */
    private ?ProgramPhase $currentPhase;

    /**
     * Ids of all done sessions, including optional ones.
     *
     * @var int[]
     */
    private array $doneSessionIds;

    /**
     * Phase id => all required sessions of the phase are done.
     *
     * @var array<int, bool>
     */
    private array $phasesDone;

    /**
     * @param int $total
     * @param int $done
     * @param ProgramSession|null $nextSession
     * @param ProgramPhase|null $currentPhase
     * @param int[] $doneSessionIds
     * @param array<int, bool> $phasesDone
     */
    public function __construct(int $total, int $done, ?ProgramSession $nextSession, ?ProgramPhase $currentPhase, array $doneSessionIds, array $phasesDone)
    {
        $this->total = $total;
        $this->done = $done;
        $this->nextSession = $nextSession;
        $this->currentPhase = $currentPhase;
        $this->doneSessionIds = $doneSessionIds;
        $this->phasesDone = $phasesDone;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getDone(): int
    {
        return $this->done;
    }

    /**
     * Returns the progress in percent, rounded down. A program without required sessions has 0 %.
     *
     * @return int
     */
    public function getPercent(): int
    {
        return $this->total > 0 ? (int)floor($this->done * 100 / $this->total) : 0;
    }

    /**
     * Whether all required sessions are done. A program without sessions is never complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->total > 0 && $this->done >= $this->total;
    }

    public function getNextSession(): ?ProgramSession
    {
        return $this->nextSession;
    }

    public function getCurrentPhase(): ?ProgramPhase
    {
        return $this->currentPhase;
    }

    public function isSessionDone(int $sessionId): bool
    {
        return in_array($sessionId, $this->doneSessionIds, true);
    }

    public function isPhaseDone(int $phaseId): bool
    {
        return $this->phasesDone[$phaseId] ?? false;
    }

    /**
     * @return int number of phases whose required sessions are all done
     */
    public function getDonePhaseCount(): int
    {
        return count(array_filter($this->phasesDone));
    }
}
