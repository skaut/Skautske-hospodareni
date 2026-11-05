<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Exception;

use RuntimeException;
use Throwable;

/** The task provably had no effect and may run later, e.g. Gmail refused it over a sending limit. */
final class TaskRetryLater extends RuntimeException
{
    public function __construct(string $message, public readonly int $delaySeconds, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
