<?php

declare(strict_types=1);

namespace App\Model\Skautis\Common\Repositories;

use App\Model\Common\Registration;
use App\Model\Common\Repositories\IRegistrationRepository;
use App\Model\Common\UnitId;
use Skaut\Skautis\Skautis;
use stdClass;

use function array_map;
use function is_array;

final class SkautisRegistrationRepository implements IRegistrationRepository
{
    public function __construct(private Skautis $skautis)
    {
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByUnit(UnitId $unitId): array
    {
        $registrations = $this->skautis->org->UnitRegistrationAll(['ID_Unit' => $unitId->toInt()]);

        if (! is_array($registrations)) {
            return []; // no results
        }

        return array_map(
            function (stdClass $registration): Registration {
                return new Registration($registration->ID, $registration->Unit, $registration->Year);
            },
            $registrations,
        );
    }
}
