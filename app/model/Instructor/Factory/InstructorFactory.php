<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Instructor\Instructor;
use Model\Participant\Payment;
use stdClass;

use function preg_match;

final class InstructorFactory
{
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public static function create(stdClass $skautisInstructor, Payment $payment): Instructor
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $skautisInstructor->Person, $matches);

        return new Instructor(
            $skautisInstructor->ID,
            $skautisInstructor->ID_Person,
            $matches['first'],
            $matches['last'],
            $matches['nick'] ?? null,
            $skautisInstructor->ID_EventEducation,
            $skautisInstructor->Event,
            $skautisInstructor->InstructorType,
            $skautisInstructor->ScoutExperience,
            $skautisInstructor->ProfessionalExperience,
            $skautisInstructor->EventFocus,
            $payment,
        );
    }
}
