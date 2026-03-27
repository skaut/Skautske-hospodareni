<?php

declare(strict_types=1);

namespace App\Model\Skautis\Common\Repositories;

use App\Model\Common\Registration;
use App\Model\Common\Repositories\IRegistrationRepository;
use App\Model\Common\UnitId;
use Skautis\Skautis;
use stdClass;

use function array_map;
use function is_object;

final class SkautisRegistrationRepository implements IRegistrationRepository
{
    public function __construct(private Skautis $skautis)
    {
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByUnit(UnitId $unitId): array
    {
        $registrations = $this->skautis->org->UnitRegistrationAll(['ID_Unit' => $unitId->toInt()]);

        if (is_object($registrations)) {
            return []; // API returns empty object when there are no results
        }

        return array_map(
            function (stdClass $registration): Registration {
                return new Registration($registration->ID, $registration->Unit, $registration->Year);
            },
            $registrations,
        );
    }
}
