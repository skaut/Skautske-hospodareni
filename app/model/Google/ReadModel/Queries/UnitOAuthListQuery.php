<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\Queries;

use Model\Common\UnitId;

/**
 * @see UnitOAuthsQueryHandler
 */
final class UnitOAuthsQuery
{
    /** @var UnitId */
    private $unitId;

    public function __construct(UnitId $unitId)
    {
        $this->unitId = $unitId;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }
}
