<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use function in_array;

/**
 * State of one queued task.
 *
 * The queue runs every task at most once. A task moves to RUNNING right before the part that cannot
 * be taken back (e.g. handing an e-mail to Gmail). A task that stays RUNNING because the process died
 * becomes UNCERTAIN and is never run again, because nobody knows whether its effect happened.
 */
enum TaskItemState: string
{
    /** Waiting for the queue. */
    case PENDING = 'pending';

    /** Taken by one run; nothing irreversible happened yet, so a dead run returns it to PENDING. */
    case CLAIMED = 'claimed';

    /** The irreversible part started. */
    case RUNNING = 'running';

    case DONE = 'done';

    /** Not needed any more, e.g. the payment was paid meanwhile. */
    case SKIPPED = 'skipped';

    /** Could not run, provably without any effect. */
    case FAILED = 'failed';

    /** The run died during the irreversible part. */
    case UNCERTAIN = 'uncertain';

    /** @return list<self> */
    public static function active(): array
    {
        return [self::PENDING, self::CLAIMED, self::RUNNING];
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }
}
