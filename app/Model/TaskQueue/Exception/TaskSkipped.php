<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Exception;

use RuntimeException;

/** The task is not needed any more, e.g. the payment was paid meanwhile. The message is for people. */
final class TaskSkipped extends RuntimeException
{
}
