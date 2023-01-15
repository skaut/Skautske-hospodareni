<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\MemberEmailsQueryHandler;

/** @see MemberEmailsQueryHandler */
final class MemberEmailsQuery
{
    public function __construct(private int $memberId)
    {
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }
}
