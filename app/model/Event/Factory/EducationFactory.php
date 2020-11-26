<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Event\Education;
use Model\Event\SkautisEducationId;
use stdClass;

final class EducationFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function create(stdClass $skautisEducation) : Education
    {
        return new Education(
            new SkautisEducationId($skautisEducation->ID),
            $skautisEducation->DisplayName,
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisEducation->StartDate),
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisEducation->EndDate),
            $skautisEducation->ID_EventEducationState
        );
    }
}
