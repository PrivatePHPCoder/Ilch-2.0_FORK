<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;

/**
 * Rules for changing the status of a participation.
 */
class Enrollments
{
    /**
     * @var ProgramStructureMapper
     */
    private ProgramStructureMapper $structureMapper;

    /**
     * @var SessionLogMapper
     */
    private SessionLogMapper $logMapper;

    public function __construct(?ProgramStructureMapper $structureMapper = null, ?SessionLogMapper $logMapper = null)
    {
        $this->structureMapper = $structureMapper ?? new ProgramStructureMapper();
        $this->logMapper = $logMapper ?? new SessionLogMapper();
    }

    /**
     * Returns the status of a participation that gets access again: completed if all sessions
     * are already done, active otherwise.
     *
     * @param EnrollmentModel $enrollment
     * @return int
     */
    public function getReactivationStatus(EnrollmentModel $enrollment): int
    {
        $progress = Progress::calculate(
            $this->structureMapper->getPhasesOfProgram($enrollment->getProgramId()),
            array_keys($this->logMapper->getDoneSessions($enrollment->getId()))
        );

        return $progress->isComplete() ? EnrollmentModel::STATUS_COMPLETED : EnrollmentModel::STATUS_ACTIVE;
    }
}
