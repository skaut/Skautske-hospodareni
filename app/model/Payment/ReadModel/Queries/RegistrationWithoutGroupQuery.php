<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Common\UnitId;
use Model\Payment\ReadModel\QueryHandlers\RegistrationWithoutGroupQueryHandler;

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
