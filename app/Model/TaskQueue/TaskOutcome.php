<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

/** How one task ended in a run of the queue. */
final class TaskOutcome
{
    public function __construct(
        public readonly TaskItemState $state,
        public readonly ?string $message = null,
    ) {
    }
}
