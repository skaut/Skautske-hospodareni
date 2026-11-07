<?php

declare(strict_types=1);

namespace App\Model\DTO\TaskQueue;

use App\Model\TaskQueue\TaskItemState;

/** A task that ended without its effect, or nobody knows whether it ran. */
final class TaskProblem
{
    public function __construct(
        public readonly string $label,
        public readonly TaskItemState $state,
        public readonly ?string $message,
    ) {
    }
}
