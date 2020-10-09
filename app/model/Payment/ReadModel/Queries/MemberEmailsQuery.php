<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\MemberEmailsQueryHandler;

/**
 * @see MemberEmailsQueryHandler
 */
final class MemberEmailsQuery
{
    private int $memberId;

    public function __construct(int $memberId)
    {
        $this->memberId = $memberId;
    }

    public function getMemberId() : int
    {
        return $this->memberId;
    }
}
