<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Scheduler;

enum CronJobResult: string
{
    case SUCCEEDED = 'ok';
    case FAILED = 'failed';
    case ALREADY_RUNNING = 'already running';
}
