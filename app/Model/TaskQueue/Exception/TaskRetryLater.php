<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Exception;

use RuntimeException;
use Throwable;

/**
 * The task provably had no effect and may run later, e.g. Gmail refused it over a sending limit.
 *
 * With $wholeBatch the other waiting tasks of the batch wait as well, without using up their attempts:
 * when the cause is not the task itself (a limit of the sender), they would only be refused too.
 */
final class TaskRetryLater extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $delaySeconds,
        ?Throwable $previous = null,
        public readonly bool $wholeBatch = false,
        public readonly int $maxAttempts = 3,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
