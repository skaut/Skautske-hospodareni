<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationCourse;
use App\Model\Event\ReadModel\Queries\EducationCoursesQuery;
use App\Model\Skautis\Factory\EducationCourseFactory;
use LogicException;
use Skautis\Skautis;

use function is_object;

class EducationCoursesQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationCourseFactory $courseFactory)
    {
    }

    /** @return array<int, EducationCourse> Education courses indexed by ID */
    public function __invoke(EducationCoursesQuery $query): array
    {
        $courses = $this->skautis->event->EventEducationCourseAll([
            'ID_EventEducation' => $query->getEventEducationID(), // TODO
        ]);

        if (is_object($courses)) {
            return [];
        }

        $result = [];
        foreach ($courses as $course) {
            $course = $this->courseFactory->create($course);
            if (! $course instanceof EducationCourse) {
                throw new LogicException('Assertion failed.');
            }
            $result[$course->getId()->toInt()] = $course;
        }

        return $result;
    }
}
