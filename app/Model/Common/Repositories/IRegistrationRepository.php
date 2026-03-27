<?php

declare(strict_types=1);

namespace App\Model\Common\Repositories;

use App\Model\Common\Registration;
use App\Model\Common\UnitId;

interface IRegistrationRepository
{
    /**
     * Returns list of registrations for given unit sorted by year in descending order.
     *
     * @return Registration[]
     */
    public function findByUnit(UnitId $unitId): array;
}
