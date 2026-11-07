<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel\QueryHandlers;

use App\Model\DTO\TaskQueue\TaskJobStatus;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueStatusQuery;
use App\Model\TaskQueue\ReadModel\TaskJobStatusReader;

final class TaskQueueStatusQueryHandler
{
    public function __construct(private TaskJobStatusReader $reader)
    {
    }

    /** @return list<TaskJobStatus> oldest first */
    public function __invoke(TaskQueueStatusQuery $query): array
    {
        return $this->reader->read($query->getScope(), $query->getNow(), $query->getFollowedJobIds());
    }
}
