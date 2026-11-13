<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

/** Totals of the stored runs of the last 24 hours. */
final class TaskQueueRunTotals
{
    public function __construct(
        public readonly int $runs,
        public readonly int $taken,
        public readonly int $done,
        public readonly int $failed,
        public readonly int $uncertain,
        public readonly int $unfinished,
        public readonly ?int $averageSeconds,
        public readonly ?int $longestSeconds,
    ) {
    }
}
