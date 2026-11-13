<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Payment\ReadModel\Queries\CountAutomaticReminderGroupsQuery;
use App\Model\Payment\Repositories\IGroupRepository;

use function count;

final class CountAutomaticReminderGroupsQueryHandler
{
    public function __construct(private IGroupRepository $groups)
    {
    }

    public function __invoke(CountAutomaticReminderGroupsQuery $query): int
    {
        return count($this->groups->findSendingAutomaticReminders());
    }
}
