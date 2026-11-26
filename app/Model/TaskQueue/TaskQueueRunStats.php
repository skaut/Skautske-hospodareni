<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

/** Counters of one cron run of the queue. */
final class TaskQueueRunStats
{
    public int $taken = 0;
    public int $done = 0;
    public int $skipped = 0;
    public int $failed = 0;
    public int $uncertain = 0;
    public int $postponed = 0;
    public int $recovered = 0;

    public function record(?TaskOutcome $outcome): void
    {
        if ($outcome === null) {
            return;
        }

        ++$this->taken;

        match ($outcome->state) {
            TaskItemState::DONE => ++$this->done,
            TaskItemState::SKIPPED => ++$this->skipped,
            TaskItemState::FAILED => ++$this->failed,
            TaskItemState::UNCERTAIN => ++$this->uncertain,
            default => ++$this->postponed,
        };
    }
}
