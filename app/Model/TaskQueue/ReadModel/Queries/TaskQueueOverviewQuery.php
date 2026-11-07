<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel\Queries;

use App\Model\TaskQueue\ReadModel\QueryHandlers\TaskQueueOverviewQueryHandler;
use DateTimeImmutable;

/**
 * Everything the administration shows about the task queue: all scopes at once.
 *
 * @see TaskQueueOverviewQueryHandler
 */
final class TaskQueueOverviewQuery
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function getNow(): DateTimeImmutable
    {
        return $this->now;
    }
}
