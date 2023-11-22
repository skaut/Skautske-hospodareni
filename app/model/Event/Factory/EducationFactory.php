<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\ChronosDate;
use Model\Common\UnitId;
use Model\Event\Education;
use Model\Event\SkautisEducationId;
use Model\Grant\SkautisGrantId;
use stdClass;

use function mb_ereg_replace;
use function property_exists;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function create(stdClass $skautisEducation): Education
    {
        $eventNameUncombined =
            ! property_exists($skautisEducation, 'DisplayNameCombined') || $skautisEducation->DisplayName === $skautisEducation->DisplayNameCombined
            ? $skautisEducation->DisplayName
            : mb_ereg_replace(' - ' . $skautisEducation->DisplayName . '$', '', $skautisEducation->DisplayNameCombined);

        return new Education(
            new SkautisEducationId($skautisEducation->ID),
            $eventNameUncombined,
            new UnitId($skautisEducation->ID_Unit),
            $skautisEducation->Unit,
            $skautisEducation->RegistrationNumber,
            $skautisEducation->StartDate === null ? null : ChronosDate::createFromFormat(self::DATETIME_FORMAT, $skautisEducation->StartDate),
            $skautisEducation->EndDate === null ? null : ChronosDate::createFromFormat(self::DATETIME_FORMAT, $skautisEducation->EndDate),
            $skautisEducation->Location ?? '',
            $skautisEducation->ID_EventEducationState ?? '',
            property_exists($skautisEducation, 'ID_Grant') && $skautisEducation->ID_Grant !== null
                ? new SkautisGrantId($skautisEducation->ID_Grant)
                : null,
        );
    }
}
