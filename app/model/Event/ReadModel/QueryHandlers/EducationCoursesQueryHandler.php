<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationCourse;
use Model\Event\ReadModel\Queries\EducationCoursesQuery;
use Model\Skautis\Factory\EducationCourseFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

class EducationCoursesQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationCourseFactory $courseFactory)
    {
    }

    /** @return array<int, Education> Educations indexed by ID */
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
            assert($course instanceof EducationCourse);

            $result[$course->getId()->toInt()] = $course;
        }

        return $result;
    }
}
