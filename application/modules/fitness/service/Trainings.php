<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\SessionLog as SessionLogMapper;

/**
 * Collects the programs a user takes part in, each with its progress.
 * Used by the frontend controllers and the progress box.
 */
class Trainings
{
    /**
     * @param int $userId
     * @return array<int, array{enrollment: \Modules\Fitness\Models\Enrollment, program: \Modules\Fitness\Models\Program, progress: \Modules\Fitness\Models\Progress}>
     */
    public function getOfUser(int $userId): array
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
}
