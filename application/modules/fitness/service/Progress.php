<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Models\ProgramPhase;
use Modules\Fitness\Models\Progress as ProgressModel;

/**
 * Calculates the progress of a participant from the sessions marked as done.
 */
class Progress
{
    /**
     * Only required sessions count for the percentage and the next session. Optional sessions
     * can be marked as done as well, but don't block the way through the program.
     *
     * @param ProgramPhase[] $phases phases with their sessions, both in their order
     * @param int[] $doneSessionIds sessions marked as done
     * @return ProgressModel
     */
    public static function calculate(array $phases, array $doneSessionIds): ProgressModel
    {
        $doneIds = array_map('intval', $doneSessionIds);
        $total = 0;
        $done = 0;
        $nextSession = null;
        $currentPhase = null;
        $phasesDone = [];
        $knownDoneIds = [];

        foreach ($phases as $phase) {
            $required = 0;
            $requiredDone = 0;
            $sessionCount = 0;
            $sessionsDone = 0;

            foreach ($phase->getSessions() as $session) {
                $isDone = in_array($session->getId(), $doneIds, true);
                $sessionCount++;
                if ($isDone) {
                    $sessionsDone++;
                    $knownDoneIds[] = $session->getId();
                }

                if ($session->isOptional()) {
                    continue;
                }

                $required++;
                $total++;
                if ($isDone) {
                    $requiredDone++;
                    $done++;
                } elseif ($nextSession === null) {
                    $nextSession = $session;
                    $currentPhase = $phase;
                }
            }

            // A phase with only optional sessions counts as done once all of them are done.
            $phasesDone[$phase->getId()] = $required > 0
                ? $requiredDone === $required
                : ($sessionCount > 0 && $sessionsDone === $sessionCount);
        }

        if ($currentPhase === null && $phases) {
            $currentPhase = end($phases);
        }

        return new ProgressModel($total, $done, $nextSession, $currentPhase, $knownDoneIds, $phasesDone);
    }
}
