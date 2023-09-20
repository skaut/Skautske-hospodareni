<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationInstructor;
use Model\Event\ReadModel\Queries\EducationInstructorsQuery;
use Model\Skautis\Factory\EducationInstructorFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

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

        if (is_object($instructors)) {
            return [];
        }

        $result = [];
        foreach ($instructors as $instructor) {
            $instructor = $this->educationInstructorFactory->create($instructor);
            assert($instructor instanceof EducationInstructor);

            $result[$instructor->getId()->toInt()] = $instructor;
        }

        return $result;
    }
}
