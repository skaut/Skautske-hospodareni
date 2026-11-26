<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Exception;

use RuntimeException;
use Throwable;

/**
 * The task could not run and provably had no effect. The message is for people.
 *
 * With $abortJob the same reason stops the rest of the batch too, e.g. an invalid Google account
 * of the sender would fail every e-mail of the batch.
 */
final class TaskFailed extends RuntimeException
{
    public function __construct(string $message, public readonly bool $abortJob = false, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
