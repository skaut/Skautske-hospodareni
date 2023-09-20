<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Event\Education;
use Model\Event\SkautisEducationId;
use Model\Event\SkautisGrantId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function create(stdClass $skautisEducation): Education
    {
        return new Education(
            new SkautisEducationId($skautisEducation->ID),
            $skautisEducation->DisplayName,
            new UnitId($skautisEducation->ID_Unit),
            $skautisEducation->Unit,
            $skautisEducation->StartDate === null ? null : Date::createFromFormat(self::DATETIME_FORMAT, $skautisEducation->StartDate),
            $skautisEducation->EndDate === null ? null : Date::createFromFormat(self::DATETIME_FORMAT, $skautisEducation->EndDate),
            $skautisEducation->Location ?? '',
            $skautisEducation->ID_EventEducationState ?? '',
            new SkautisGrantId($skautisEducation->ID_Grant ?? null),
        );
    }
}
