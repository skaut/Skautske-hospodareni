<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationInstructor;
use App\Model\Event\ReadModel\Queries\EducationInstructorsQuery;
use App\Model\Skautis\Factory\EducationInstructorFactory;
use LogicException;
use Skaut\Skautis\Skautis;

use function is_array;

class EducationInstructorsQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationInstructorFactory $educationInstructorFactory)
    {
    }

    /** @return array<int, EducationInstructor> Education instructors indexed by ID */
    public function __invoke(EducationInstructorsQuery $query): array
    {
        $instructors = $this->skautis->event->InstructorAll([
            'ID_EventEducation' => $query->getEventEducationId(),
        ]);

        if (! is_array($instructors)) {
            return [];
        }

        $result = [];
        foreach ($instructors as $instructor) {
            $instructor = $this->educationInstructorFactory->create($instructor);
            if (! $instructor instanceof EducationInstructor) {
                throw new LogicException('Assertion failed.');
            }
            $result[$instructor->getId()->toInt()] = $instructor;
        }

        return $result;
    }
}
