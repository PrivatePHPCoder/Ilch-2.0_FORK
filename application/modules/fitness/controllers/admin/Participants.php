<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Pagination;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Service\Enrollments;
use Modules\Fitness\Service\Progress;
use Modules\User\Mappers\Notifications as NotificationsMapper;
use Modules\User\Models\Notification as NotificationModel;

/**
 * Participants of the programs: progress, pausing and ending a participation.
 */
class Participants extends Base
{
    /**
     * Status changes an admin can make, with the translation key of the notification for the user.
     *
     * @var array<string, string>
     */
    private const CHANGES = [
        'pause' => 'participationPausedNotification',
        'revoke' => 'participationRevokedNotification',
        'reactivate' => 'participationReactivatedNotification',
    ];

    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuParticipants'), ['action' => 'index']);

        $programs = [];
        foreach ((new ProgramMapper())->getPrograms() as $program) {
            $programs[$program->getId()] = $program;
        }

        [$programId, $status] = $this->getFilter($programs);
        $where = [];
        if ($programId) {
            $where['en.program_id'] = $programId;
        }
        if ($status) {
            $where['en.status'] = $status;
        }

        $pagination = new Pagination();
        $pagination->setRowsPerPage($this->getConfig()->get('defaultPaginationObjects'));
        $pagination->setPage($this->getRequest()->getParam('page', 1));

        $enrollments = (new EnrollmentMapper())->getEntriesBy($where, ['en.started_at' => 'DESC', 'en.id' => 'DESC'], $pagination);
        $logs = (new SessionLogMapper())->getDoneSessionsOfEnrollments(array_map(static fn ($enrollment) => $enrollment->getId(), $enrollments));

        $structureMapper = new ProgramStructureMapper();
        $phasesOfProgram = [];
        $participants = [];
        foreach ($enrollments as $enrollment) {
            $phasesOfProgram[$enrollment->getProgramId()] ??= $structureMapper->getPhasesOfProgram($enrollment->getProgramId());
            $doneSessions = $logs[$enrollment->getId()] ?? [];

            $participants[] = [
                'enrollment' => $enrollment,
                'progress' => Progress::calculate($phasesOfProgram[$enrollment->getProgramId()], array_keys($doneSessions)),
                'lastActivity' => $doneSessions ? max($doneSessions) : null,
            ];
        }

        $this->getView()->set('participants', $participants)
            ->set('programs', $programs)
            ->set('programId', $programId)
            ->set('status', $status)
            ->set('pagination', $pagination);
    }

    /**
     * Pauses, ends or reactivates a participation. Only reachable by POST.
     */
    public function statusAction()
    {
        $filter = array_filter([
            'program' => (int)$this->getRequest()->getPost('program'),
            'status' => (int)$this->getRequest()->getPost('status'),
            'page' => (int)$this->getRequest()->getPost('page'),
        ]);
        $back = array_merge(['action' => 'index'], $filter);

        if (!$this->getRequest()->isPost()) {
            $this->redirect($back);
        }

        $enrollmentMapper = new EnrollmentMapper();
        $enrollment = $enrollmentMapper->getEnrollmentById((int)$this->getRequest()->getPost('id'));
        if (!$enrollment) {
            $this->redirect()
                ->withMessage('entryNotFound', 'danger')
                ->to($back);
        }

        $change = (string)$this->getRequest()->getPost('change');
        $newStatus = $this->getNewStatus($enrollment, $change);
        if ($newStatus === null) {
            $this->redirect()
                ->withMessage('participationChangeInvalid', 'warning')
                ->to($back);
        }

        $enrollmentMapper->updateStatus($enrollment, $newStatus);

        $message = $this->getTranslator()->trans(self::CHANGES[$change], $enrollment->getProgramTitle());
        (new NotificationsMapper())->addNotification((new NotificationModel())
            ->setUserId($enrollment->getUserId())
            ->setModule('fitness')
            ->setMessage(mb_substr($message, 0, 255))
            ->setURL($this->getLayout()->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'show', 'id' => $enrollment->getProgramId()], ''))
            ->setType('participationChanged'));

        $this->redirect()
            ->withMessage('saveSuccess')
            ->to($back);
    }

    /**
     * Returns the status after a change, or null if the change is not possible in the current status.
     * A reactivated participation is completed again if all sessions are already done.
     *
     * @param EnrollmentModel $enrollment
     * @param string $change one of the keys of CHANGES
     * @return int|null
     */
    private function getNewStatus(EnrollmentModel $enrollment, string $change): ?int
    {
        switch ($change) {
            case 'pause':
                return $enrollment->getStatus() === EnrollmentModel::STATUS_ACTIVE ? EnrollmentModel::STATUS_PAUSED : null;
            case 'revoke':
                return $enrollment->getStatus() !== EnrollmentModel::STATUS_REVOKED ? EnrollmentModel::STATUS_REVOKED : null;
            case 'reactivate':
                return $enrollment->grantsAccess() ? null : (new Enrollments())->getReactivationStatus($enrollment);
            default:
                return null;
        }
    }

    /**
     * Reads the program and status filter from the request. Unknown values are ignored.
     *
     * @param array<int, \Modules\Fitness\Models\Program> $programs
     * @return array{int, int} program id and status, 0 means all
     */
    private function getFilter(array $programs): array
    {
        $programId = (int)$this->getRequest()->getParam('program');
        $status = (int)$this->getRequest()->getParam('status');

        return [
            isset($programs[$programId]) ? $programId : 0,
            isset(EnrollmentModel::STATUSES[$status]) ? $status : 0,
        ];
    }
}
