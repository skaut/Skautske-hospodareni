<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\Queries;

use Model\Common\UnitId;
use Model\Google\ReadModel\QueryHandlers\UnitOAuthListQueryHandler;

/** @see UnitOAuthListQueryHandler */
final class UnitOAuthListQuery
{
    public function __construct(private UnitId $unitId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }
}
