<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

use App\Model\TaskQueue\TaskQueueRunStopReason;
use DateTimeImmutable;

final class TaskQueueRunRow
{
    /** A run is cut off when it did not finish within the time any request may take. */
    private const CUT_OFF_AFTER_SECONDS = 600;

    public function __construct(
        public readonly int $id,
        public readonly DateTimeImmutable $startedAt,
        public readonly ?DateTimeImmutable $finishedAt,
        public readonly int $taken,
        public readonly int $done,
        public readonly int $skipped,
        public readonly int $failed,
        public readonly int $uncertain,
        public readonly int $postponed,
        public readonly int $recovered,
        public readonly ?TaskQueueRunStopReason $stopReason,
        public readonly ?int $pendingAfter,
    ) {
    }

    public function getDurationSeconds(): ?int
    {
        return $this->finishedAt === null ? null : $this->finishedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function isCutOff(DateTimeImmutable $now): bool
    {
        return $this->finishedAt === null && $now->getTimestamp() - $this->startedAt->getTimestamp() > self::CUT_OFF_AFTER_SECONDS;
    }
}
