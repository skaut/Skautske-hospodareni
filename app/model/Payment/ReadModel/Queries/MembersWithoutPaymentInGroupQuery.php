<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Common\UnitId;
use Model\Payment\ReadModel\QueryHandlers\MembersWithoutPaymentInGroupQueryHandler;

/**
 * @see MembersWithoutPaymentInGroupQueryHandler
 */
final class MembersWithoutPaymentInGroupQuery
{
    /** @var UnitId */
    private $unitId;

    /** @var int */
    private $groupId;

    public function __construct(UnitId $unitId, int $groupId)
    {
        $this->unitId  = $unitId;
        $this->groupId = $groupId;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }
}
