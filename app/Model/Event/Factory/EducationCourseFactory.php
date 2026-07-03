<?php

declare(strict_types=1);

namespace App\Model\Skautis\Factory;

use App\Model\Event\EducationCourse;
use App\Model\Event\SkautisEducationCourseId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationCourseFactory
{
    public function create(stdClass $skautisEducation): EducationCourse
    {
        return new EducationCourse(
            new SkautisEducationCourseId($skautisEducation->ID),
            $skautisEducation->DisplayName ?? null,
            $skautisEducation->PersonDays,
        );
    }
}
