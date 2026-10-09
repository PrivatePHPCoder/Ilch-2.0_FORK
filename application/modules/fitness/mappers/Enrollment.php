<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;
use Ilch\Pagination;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;

class Enrollment extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_enrollments';

    /**
     * Returns enrollments with program title and user name.
     *
     * @param array $where
     * @param array $orderBy
     * @param Pagination|null $pagination
     * @return EnrollmentModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['en.started_at' => 'DESC', 'en.id' => 'DESC'], ?Pagination $pagination = null): array
    {
        $select = $this->db()->select(['en.id', 'en.program_id', 'en.user_id', 'en.status', 'en.source', 'en.order_id', 'en.started_at', 'en.completed_at', 'en.access_until'])
            ->from(['en' => $this->tablename])
            ->join(['p' => 'fitness_programs'], 'p.id = en.program_id', 'INNER', ['program_title' => 'p.title'])
            ->join(['u' => 'users'], 'u.id = en.user_id', 'LEFT', ['user_name' => 'u.name'])
            ->where($where)
            ->order($orderBy);

        if ($pagination !== null) {
            $select->limit($pagination->getLimit())
                ->useFoundRows();
            $result = $select->execute();
            $pagination->setRows($result->getFoundRows());
        } else {
            $result = $select->execute();
        }

        $rows = $result->fetchRows();

        $enrollments = [];
        foreach ($rows as $row) {
            $enrollments[] = (new EnrollmentModel())->setByArray($row);
        }

        return $enrollments;
    }

    public function getEnrollmentById(int $id): ?EnrollmentModel
    {
        $enrollments = $this->getEntriesBy(['en.id' => $id]);

        return reset($enrollments) ?: null;
    }

    /**
     * Returns the enrollment of a user in a program.
     *
     * @param int $programId
     * @param int $userId
     * @return EnrollmentModel|null
     */
    public function getEnrollment(int $programId, int $userId): ?EnrollmentModel
    {
        $enrollments = $this->getEntriesBy(['en.program_id' => $programId, 'en.user_id' => $userId]);

        return reset($enrollments) ?: null;
    }

    /**
     * @param int $userId
     * @return EnrollmentModel[]
     */
    public function getEnrollmentsOfUser(int $userId): array
    {
        return $this->getEntriesBy(['en.user_id' => $userId]);
    }

    /**
     * @param int $programId
     * @return EnrollmentModel[]
     */
    public function getEnrollmentsOfProgram(int $programId): array
    {
        return $this->getEntriesBy(['en.program_id' => $programId]);
    }

    /**
     * Lets a user take part in a program. An existing enrollment is returned unchanged.
     *
     * @param int $programId
     * @param int $userId
     * @param int $source one of EnrollmentModel::SOURCE_*
     * @param int|null $orderId
     * @return EnrollmentModel|null null if the enrollment could not be stored
     */
    public function enroll(int $programId, int $userId, int $source = EnrollmentModel::SOURCE_FREE, ?int $orderId = null): ?EnrollmentModel
    {
        $existing = $this->getEnrollment($programId, $userId);
        if ($existing) {
            return $existing;
        }

        $enrollment = (new EnrollmentModel())
            ->setProgramId($programId)
            ->setUserId($userId)
            ->setSource($source)
            ->setOrderId($orderId)
            ->setStartedAt((new Date())->toDb());

        try {
            $this->db()->insert($this->tablename)
                ->values($enrollment->getArray(false))
                ->execute();
        } catch (\Exception $exception) {
            // A second request was faster (unique program and user). Its enrollment is used below.
        }

        return $this->getEnrollment($programId, $userId);
    }

    /**
     * Changes the status. Completing sets the completion date, every other status removes it.
     *
     * @param EnrollmentModel $enrollment
     * @param int $status one of EnrollmentModel::STATUS_*
     */
    public function updateStatus(EnrollmentModel $enrollment, int $status): void
    {
        $enrollment->setStatus($status)
            ->setCompletedAt($status === EnrollmentModel::STATUS_COMPLETED ? ($enrollment->getCompletedAt() ?? (new Date())->toDb()) : null);

        $this->updateRow($this->tablename, $enrollment->getId(), [
            'status' => $enrollment->getStatus(),
            'completed_at' => $enrollment->getCompletedAt(),
        ]);
    }

    /**
     * Stores where the participation comes from, for example from a paid order.
     *
     * @param EnrollmentModel $enrollment
     * @param int $source one of EnrollmentModel::SOURCE_*
     * @param int|null $orderId
     */
    public function updateSource(EnrollmentModel $enrollment, int $source, ?int $orderId): void
    {
        $enrollment->setSource($source)
            ->setOrderId($orderId);

        $this->updateRow($this->tablename, $enrollment->getId(), [
            'source' => $enrollment->getSource(),
            'order_id' => $enrollment->getOrderId(),
        ]);
    }

    /**
     * Returns the number of participants per program.
     *
     * @return array<int, int> program id => number of enrollments
     */
    public function getCountsPerProgram(): array
    {
        $rows = $this->db()->select(['program_id', 'participant_count' => 'COUNT(*)'])
            ->from($this->tablename)
            ->group(['program_id'])
            ->execute()
            ->fetchRows();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['program_id']] = (int)$row['participant_count'];
        }

        return $counts;
    }
}
