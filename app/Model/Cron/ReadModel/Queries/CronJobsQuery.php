<?php

declare(strict_types=1);

namespace App\Model\Cron\ReadModel\Queries;

use App\Model\Cron\ReadModel\QueryHandlers\CronJobsQueryHandler;
use DateTimeImmutable;

/** @see CronJobsQueryHandler */
final class CronJobsQuery
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function getNow(): DateTimeImmutable
    {
        return $this->now;
    }
}
