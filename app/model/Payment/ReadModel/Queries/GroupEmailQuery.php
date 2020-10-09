<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\EmailType;
use Model\Payment\ReadModel\QueryHandlers\GroupEmailQueryHandler;

/**
 * @see GroupEmailQueryHandler
 */
final class GroupEmailQuery
{
    private int $groupId;

    private EmailType $emailType;

    public function __construct(int $groupId, EmailType $emailType)
    {
        $this->groupId   = $groupId;
        $this->emailType = $emailType;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }

    public function getEmailType() : EmailType
    {
        return $this->emailType;
    }
}
