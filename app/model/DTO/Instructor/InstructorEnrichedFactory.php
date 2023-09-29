<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use Model\DTO\Instructor\Instructor;
use Model\DTO\Instructor\InstructorEnriched;
use Nette\StaticClass;
use stdClass;

use function property_exists;

final class InstructorEnrichedFactory
{
    use StaticClass;

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public static function create(Instructor $instructor, stdClass $skautisPerson): InstructorEnriched
    {
        return new InstructorEnriched(
            $instructor,
            $skautisPerson->Street,
            $skautisPerson->City,
            $skautisPerson->Postcode,
            property_exists($skautisPerson, 'Birthday')
                ? new Date($skautisPerson->Birthday)
                : null,
        );
    }
}
