<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\TaskQueue\EnqueueResult;
use App\Model\TaskQueue\TaskOutcome;

/** What happened to e-mails a user sent: either one went out right away, or they wait in the queue. */
final class PaymentEmailDispatch
{
    private function __construct(
        public readonly int $queued,
        public readonly int $alreadyQueued,
        public readonly ?TaskOutcome $sentNow,
    ) {
    }

    public static function queued(EnqueueResult $result): self
    {
        return new self($result->queued, $result->alreadyQueued, null);
    }

    public static function sentNow(TaskOutcome $outcome): self
    {
        return new self(0, 0, $outcome);
    }
}
