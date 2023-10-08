<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Event\EducationInstructor;
use Model\Event\SkautisEducationInstructorId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationInstructorFactory
{
    public function create(stdClass $skautisEducationTerm): EducationInstructor
    {
        return new EducationInstructor(
            new SkautisEducationInstructorId($skautisEducationTerm->ID),
        );
    }
}
