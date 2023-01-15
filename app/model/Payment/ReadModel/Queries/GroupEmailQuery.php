<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\EmailType;
use Model\Payment\ReadModel\QueryHandlers\GroupEmailQueryHandler;

/** @see GroupEmailQueryHandler */
final class GroupEmailQuery
{
    public function __construct(private int $groupId, private EmailType $emailType)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getEmailType(): EmailType
    {
        return $this->emailType;
    }
}
