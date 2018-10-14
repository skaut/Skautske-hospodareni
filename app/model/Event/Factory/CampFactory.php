<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Event\Camp;
use Model\Event\SkautisCampId;

final class CampFactory implements ICampFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function create(\stdClass $skautisCamp) : Camp
    {
        return new Camp(
            new SkautisCampId($skautisCamp->ID),
            $skautisCamp->DisplayName,
            $skautisCamp->ID_Unit,
            $skautisCamp->Unit,
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisCamp->StartDate),
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisCamp->EndDate),
            $skautisCamp->Location,
            $skautisCamp->ID_EventCampState,
            $skautisCamp->RegistrationNumber
        );
    }
}
