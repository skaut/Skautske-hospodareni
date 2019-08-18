<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Event\Camp;
use Model\Event\SkautisCampId;
use stdClass;
use function array_map;
use function is_array;
use function is_string;
use function property_exists;

final class CampFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function create(stdClass $skautisCamp) : Camp
    {
        //dump ($skautisCamp);die();
        return new Camp(
            new SkautisCampId($skautisCamp->ID),
            $skautisCamp->DisplayName,
            new UnitId($skautisCamp->ID_Unit),
            $skautisCamp->Unit,
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisCamp->StartDate),
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisCamp->EndDate),
            $skautisCamp->Location,
            $skautisCamp->ID_EventCampState,
            $skautisCamp->RegistrationNumber,
            $this->getParticipatingUnits($skautisCamp->ID_UnitArray),
            $skautisCamp->TotalDays,
            $skautisCamp->RealAdult,
            $skautisCamp->RealChild,
            $skautisCamp->RealCount,
            $skautisCamp->RealChildDays,
            $skautisCamp->RealPersonDays,
            $skautisCamp->IsRealAutoComputed
        );
    }

    /**
     * @return UnitId[]
     */
    private function getParticipatingUnits(stdClass $idUnitArray) : array
    {
        if (property_exists($idUnitArray, 'string')) {
            $unitIdOrIds = $idUnitArray->string;

            if (is_array($unitIdOrIds)) {
                return array_map(
                    function (string $id) {
                        return new UnitId((int) $id);
                    },
                    $unitIdOrIds
                );
            }

            if (is_string($unitIdOrIds)) {
                return [new UnitId((int) $unitIdOrIds)];
            }
        }

        return [];
    }
}
