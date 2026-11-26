<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\ReadModel\QueryHandlers;

use App\Model\TaskQueue\ReadModel\Queries\CountWaitingTasksQuery;
use App\Model\TaskQueue\Repository\TaskQueueRepository;

final class CountWaitingTasksQueryHandler
{
    public function __construct(private TaskQueueRepository $repository)
    {
    }

    public function __invoke(CountWaitingTasksQuery $query): int
    {
        return $this->repository->countWaiting($query->scope);
    }
}
