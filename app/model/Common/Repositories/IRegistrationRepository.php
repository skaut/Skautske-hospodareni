<?php

declare(strict_types=1);

namespace Model\Common\Repositories;

use Model\Common\Registration;
use Model\Common\UnitId;

interface IRegistrationRepository
{
    /**
     * Returns list of registrations for given unit sorted by year in descending order
     *
     * @return Registration[]
     */
    public function findByUnit(UnitId $unitId) : array;
}
