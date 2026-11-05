<?php

declare(strict_types=1);

namespace App\Presentation\Admin\TaskQueue;

use App\Model\Cron\ReadModel\Queries\CronJobsQuery;
use App\Model\Cron\Services\CronJobRegistry;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueOverviewQuery;
use App\Presentation\Admin\AdminBasePresenter;
use DateTimeImmutable;

/**
 * State of all background batches across groups and statistics of the last runs of the queue.
 */
final class TaskQueuePresenter extends AdminBasePresenter
{
    public function renderDefault(): void
    {
        $now = new DateTimeImmutable();

        $this->template->setParameters([
            'overview' => $this->queryBus->handle(new TaskQueueOverviewQuery($now)),
            'queueJob' => $this->queryBus->handle(new CronJobsQuery($now))[CronJobRegistry::TASK_QUEUE] ?? null,
            'now' => $now,
        ]);
    }
}
