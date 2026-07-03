<?php

declare(strict_types=1);

namespace App\Model\Skautis\Factory;

use App\Model\Event\EducationInstructor;
use App\Model\Event\SkautisEducationInstructorId;
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
