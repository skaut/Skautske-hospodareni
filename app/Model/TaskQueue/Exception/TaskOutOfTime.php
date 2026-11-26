<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Exception;

use RuntimeException;

/**
 * The run has no time left for the irreversible step of the task: it would not finish within the limit of
 * the request. Nothing happened, the task waits for the next run and the attempt does not count.
 */
final class TaskOutOfTime extends RuntimeException
{
}
