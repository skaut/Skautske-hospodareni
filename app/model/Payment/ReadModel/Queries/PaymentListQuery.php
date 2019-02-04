<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

final class PaymentListQuery
{
    /** @var int */
    private $groupId;

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }
}
