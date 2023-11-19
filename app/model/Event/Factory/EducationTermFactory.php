<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\ChronosDate;
use Model\Event\EducationTerm;
use Model\Event\SkautisEducationLocationId;
use Model\Event\SkautisEducationTermId;
use stdClass;

use function explode;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationTermFactory
{
    private const DATETIME_FORMAT = 'd.m.Y';

    public function create(stdClass $skautisEducationTerm): EducationTerm
    {
        [$startDate, $endDate] = explode('-', $skautisEducationTerm->EventEducationTerm);

        return new EducationTerm(
            new SkautisEducationTermId($skautisEducationTerm->ID),
            ChronosDate::createFromFormat(self::DATETIME_FORMAT, $startDate),
            ChronosDate::createFromFormat(self::DATETIME_FORMAT, $endDate),
            new SkautisEducationLocationId($skautisEducationTerm->ID_EventEducationLocation),
        );
    }
}
