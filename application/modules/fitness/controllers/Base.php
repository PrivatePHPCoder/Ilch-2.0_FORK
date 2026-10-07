<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Ilch\Controller\Frontend;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;
use Modules\Fitness\Service\Access;
use Modules\Fitness\Service\Progress;

/**
 * Common base for all frontend controllers of the fitness module.
 *
 * Controllers that define their own init() have to call parent::init().
 */
class Base extends Frontend
{
    /**
     * Group id of guests.
     */
    private const GROUP_GUEST = 3;

    public function init()
    {
        $this->useFitnessLayout();

        $this->getLayout()->header()
            ->css('static/css/fitness.css')
            ->js('static/js/fitness.js');
    }

    /**
     * Shows the page in the layout file of this module, if that is turned on in the settings.
     *
     * Only the default file of the active layout gets replaced. A file chosen by the maintenance
     * mode or by a layout route of the active layout (for example
     * 'layouts' => ['fitness' => [['module' => 'fitness']]]) stays untouched.
     */
    protected function useFitnessLayout(): void
    {
        $layout = $this->getLayout();
        $layoutKey = $layout->getLayoutKey();

        if ($this->getConfig()->get('fitness_ownLayout') && $layout->getFile() === 'layouts/' . $layoutKey . '/index') {
            $layout->setFile('modules/fitness/layouts/fitness', $layoutKey);
        }
    }

    /**
     * Returns the group ids of the current visitor. Guests belong to the guest group.
     *
     * @return int[]
     */
    protected function getVisitorGroupIds(): array
    {
        $user = $this->getUser();

        return $user ? array_map('intval', array_keys($user->getGroups())) : [self::GROUP_GUEST];
    }

    /**
     * Returns whether the visitor may manage the fitness module. Such visitors may preview
     * unpublished programs and inactive exercises.
     *
     * @return bool
     */
    protected function canManageFitness(): bool
    {
        return Access::canManage($this->getUser());
    }

    /**
     * Returns the programs a user takes part in, each with its progress.
     *
     * @param int $userId
     * @return array<int, array{enrollment: \Modules\Fitness\Models\Enrollment, program: \Modules\Fitness\Models\Program, progress: \Modules\Fitness\Models\Progress}>
     */
    protected function getTrainingsOfUser(int $userId): array
    {
        $programMapper = new ProgramMapper();
        $structureMapper = new ProgramStructureMapper();
        $logMapper = new SessionLogMapper();

        $trainings = [];
        foreach ((new EnrollmentMapper())->getEnrollmentsOfUser($userId) as $enrollment) {
            $program = $programMapper->getProgramById($enrollment->getProgramId());
            if (!$program) {
                continue;
            }

            $trainings[] = [
                'enrollment' => $enrollment,
                'program' => $program,
                'progress' => Progress::calculate(
                    $structureMapper->getPhasesOfProgram($program->getId()),
                    array_keys($logMapper->getDoneSessions($enrollment->getId()))
                ),
            ];
        }

        return $trainings;
    }

    /**
     * Sends guests to the login page.
     */
    protected function requireLogin(): void
    {
        if (!$this->getUser()) {
            $this->redirect()
                ->withMessage('loginRequired', 'info')
                ->to(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
        }
    }
}
