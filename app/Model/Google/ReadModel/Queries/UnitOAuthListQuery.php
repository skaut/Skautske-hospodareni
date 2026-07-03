<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\Queries;

use App\Model\Common\UnitId;
use App\Model\Google\ReadModel\QueryHandlers\UnitOAuthListQueryHandler;

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
