<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;

/**
 * Sessions a participant has marked as done.
 */
class SessionLog extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_session_logs';

    /**
     * Returns the done sessions of an enrollment.
     *
     * @param int $enrollmentId
     * @return array<int, string> session id => date and time it was marked as done
     */
    public function getDoneSessions(int $enrollmentId): array
    {
        $rows = $this->db()->select(['program_session_id', 'completed_at'])
            ->from($this->tablename)
            ->where(['enrollment_id' => $enrollmentId])
            ->order(['completed_at' => 'ASC', 'id' => 'ASC'])
            ->execute()
            ->fetchRows();

        $sessions = [];
        foreach ($rows as $row) {
            $sessions[(int)$row['program_session_id']] = $row['completed_at'];
        }

        return $sessions;
    }

    /**
     * Marks a session as done.
     *
     * @param int $enrollmentId
     * @param int $sessionId
     * @return bool false if it was already marked as done
     */
    public function markDone(int $enrollmentId, int $sessionId): bool
    {
        if ($this->isDone($enrollmentId, $sessionId)) {
            return false;
        }

        try {
            $this->db()->insert($this->tablename)
                ->values([
                    'enrollment_id' => $enrollmentId,
                    'program_session_id' => $sessionId,
                    'completed_at' => (new Date())->toDb(),
                    'note' => '',
                ])
                ->execute();
        } catch (\Exception $exception) {
            // A second request was faster (unique enrollment and session).
            return false;
        }

        return true;
    }

    /**
     * Removes the mark of a session, for example after a wrong click.
     *
     * @param int $enrollmentId
     * @param int $sessionId
     * @return bool false if the session was not marked as done
     */
    public function unmarkDone(int $enrollmentId, int $sessionId): bool
    {
        return (bool)$this->db()->delete($this->tablename)
            ->where(['enrollment_id' => $enrollmentId, 'program_session_id' => $sessionId])
            ->execute();
    }

    public function isDone(int $enrollmentId, int $sessionId): bool
    {
        return (bool)$this->db()->select('COUNT(*)')
            ->from($this->tablename)
            ->where(['enrollment_id' => $enrollmentId, 'program_session_id' => $sessionId])
            ->execute()
            ->fetchCell();
    }
}
