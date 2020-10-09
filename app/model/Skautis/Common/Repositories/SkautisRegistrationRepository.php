<?php

declare(strict_types=1);

namespace Model\Skautis\Common\Repositories;

use Model\Common\Registration;
use Model\Common\Repositories\IRegistrationRepository;
use Model\Common\UnitId;
use Skautis\Skautis;
use stdClass;
use function array_map;
use function is_object;

final class SkautisRegistrationRepository implements IRegistrationRepository
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * {@inheritDoc}
     */
    public function findByUnit(UnitId $unitId) : array
    {
        $registrations = $this->skautis->org->UnitRegistrationAll(['ID_Unit' => $unitId->toInt()]);

        if (is_object($registrations)) {
            return []; // API returns empty object when there are no results
        }

        return array_map(
            function (stdClass $registration) : Registration {
                return new Registration($registration->ID, $registration->Unit, $registration->Year);
            },
            $registrations
        );
    }
}
