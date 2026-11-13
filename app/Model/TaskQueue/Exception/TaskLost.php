<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Exception;

use RuntimeException;

/** Another run took the task over, e.g. after this run was stalled for too long. Nothing may happen then. */
final class TaskLost extends RuntimeException
{
}
