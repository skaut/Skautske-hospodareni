<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

/** Why a cron run of the queue stopped taking tasks. */
enum TaskQueueRunStopReason: string
{
    /** Nothing more was waiting. */
    case EMPTY = 'empty';

    /** The time budget of the request ran out; the rest waits for the next minute. */
    case DEADLINE = 'deadline';

    /** A database error closed the entity manager; the next run continues. */
    case ERROR = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::EMPTY => 'fronta je prázdná',
            self::DEADLINE => 'vypršel čas běhu',
            self::ERROR => 'chyba databáze',
        };
    }
}
