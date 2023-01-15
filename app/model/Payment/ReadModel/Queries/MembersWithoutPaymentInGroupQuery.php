<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Common\UnitId;
use Model\Payment\ReadModel\QueryHandlers\MembersWithoutPaymentInGroupQueryHandler;

/** @see MembersWithoutPaymentInGroupQueryHandler */
final class MembersWithoutPaymentInGroupQuery
{
    public function __construct(private UnitId $unitId, private int $groupId, private bool $directMemberOnly)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function isDirectMemberOnly(): bool
    {
        return $this->directMemberOnly;
    }
}
