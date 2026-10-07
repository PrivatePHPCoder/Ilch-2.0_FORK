<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Modules\Fitness\Models\ProgramPhase as ProgramPhaseModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;

/**
 * Phases and sessions of a program.
 */
class ProgramStructure extends Base
{
    /**
     * @var string
     */
    public string $tablenamePhases = 'fitness_program_phases';

    /**
     * @var string
     */
    public string $tablenameSessions = 'fitness_program_sessions';

    /**
     * Returns the phases of a program with their sessions, both in their order.
     *
     * @param int $programId
     * @return ProgramPhaseModel[]
     */
    public function getPhasesOfProgram(int $programId): array
    {
        $phaseRows = $this->db()->select(['id', 'program_id', 'position', 'title', 'description'])
            ->from($this->tablenamePhases)
            ->where(['program_id' => $programId])
            ->order(['position' => 'ASC', 'id' => 'ASC'])
            ->execute()
            ->fetchRows();

        $sessionRows = $this->db()->select(['s.id', 's.program_id', 's.phase_id', 's.workout_id', 's.position', 's.title', 's.day_hint', 's.is_optional'])
            ->from(['s' => $this->tablenameSessions])
            ->join(['w' => 'fitness_workouts'], 'w.id = s.workout_id', 'INNER', ['workout_title' => 'w.title'])
            ->where(['s.program_id' => $programId])
            ->order(['s.position' => 'ASC', 's.id' => 'ASC'])
            ->execute()
            ->fetchRows();

        $sessionsPerPhase = [];
        foreach ($sessionRows as $row) {
            $sessionsPerPhase[(int)$row['phase_id']][] = (new ProgramSessionModel())->setByArray($row);
        }

        $phases = [];
        foreach ($phaseRows as $row) {
            $phases[] = (new ProgramPhaseModel())->setByArray($row)
                ->setSessions($sessionsPerPhase[(int)$row['id']] ?? []);
        }

        return $phases;
    }

    /**
     * Returns a single session with the title of its workout.
     *
     * @param int $id
     * @return ProgramSessionModel|null
     */
    public function getSessionById(int $id): ?ProgramSessionModel
    {
        $row = $this->db()->select(['s.id', 's.program_id', 's.phase_id', 's.workout_id', 's.position', 's.title', 's.day_hint', 's.is_optional'])
            ->from(['s' => $this->tablenameSessions])
            ->join(['w' => 'fitness_workouts'], 'w.id = s.workout_id', 'INNER', ['workout_title' => 'w.title'])
            ->where(['s.id' => $id])
            ->execute()
            ->fetchAssoc();

        return $row ? (new ProgramSessionModel())->setByArray($row) : null;
    }

    /**
     * Stores the phases and sessions of a program in the given order.
     *
     * Phases and sessions that already belong to the program are updated and keep their id,
     * new ones are inserted and missing ones are deleted. Stable session ids matter: the
     * progress of participants refers to them.
     *
     * @param int $programId
     * @param ProgramPhaseModel[] $phases in their order, each with its sessions in their order
     */
    public function syncStructure(int $programId, array $phases): void
    {
        $existingPhaseIds = $this->getIdsOfProgram($this->tablenamePhases, $programId);
        $existingSessionIds = $this->getIdsOfProgram($this->tablenameSessions, $programId);
        $keptPhaseIds = [];
        $keptSessionIds = [];

        foreach (array_values($phases) as $phasePosition => $phase) {
            $phase->setProgramId($programId)
                ->setPosition($phasePosition);

            if ($phase->getId() && in_array($phase->getId(), $existingPhaseIds, true)) {
                $this->updateRow($this->tablenamePhases, $phase->getId(), $phase->getArray(false));
                $keptPhaseIds[] = $phase->getId();
            } else {
                $phase->setId((int)$this->db()->insert($this->tablenamePhases)
                    ->values($phase->getArray(false))
                    ->execute());
            }

            foreach (array_values($phase->getSessions()) as $sessionPosition => $session) {
                $session->setProgramId($programId)
                    ->setPhaseId($phase->getId())
                    ->setPosition($sessionPosition);

                if ($session->getId() && in_array($session->getId(), $existingSessionIds, true)) {
                    $this->updateRow($this->tablenameSessions, $session->getId(), $session->getArray(false));
                    $keptSessionIds[] = $session->getId();
                } else {
                    $session->setId((int)$this->db()->insert($this->tablenameSessions)
                        ->values($session->getArray(false))
                        ->execute());
                }
            }
        }

        // Sessions first: a removed session might sit in a phase that stays.
        $this->deleteRowsOfProgram($this->tablenameSessions, $programId, array_diff($existingSessionIds, $keptSessionIds));
        $this->deleteRowsOfProgram($this->tablenamePhases, $programId, array_diff($existingPhaseIds, $keptPhaseIds));
    }

    /**
     * @param string $table
     * @param int $programId
     * @return int[]
     */
    private function getIdsOfProgram(string $table, int $programId): array
    {
        return array_map('intval', $this->db()->select('id')
            ->from($table)
            ->where(['program_id' => $programId])
            ->execute()
            ->fetchList());
    }

    /**
     * @param string $table
     * @param int $programId
     * @param int[] $ids
     */
    private function deleteRowsOfProgram(string $table, int $programId, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $this->db()->delete($table)
            ->where(['program_id' => $programId, 'id' => array_values($ids)])
            ->execute();
    }
}
