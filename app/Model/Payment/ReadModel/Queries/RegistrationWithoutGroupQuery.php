<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Common\UnitId;
use App\Model\Payment\ReadModel\QueryHandlers\RegistrationWithoutGroupQueryHandler;

/** @see RegistrationWithoutGroupQueryHandler */
final class RegistrationWithoutGroupQuery
{
    public function __construct(private UnitId $unitId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }
}
