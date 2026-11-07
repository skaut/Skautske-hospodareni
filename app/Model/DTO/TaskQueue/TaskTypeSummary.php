<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

/** State of one type of tasks: what waits now and how the last 24 hours went. */
final class TaskTypeSummary
{
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly int $pending,
        public readonly int $running,
        public readonly int $doneLastDay,
        public readonly int $skippedLastDay,
        public readonly int $failedLastDay,
        public readonly int $uncertainLastDay,
    ) {
    }
}
