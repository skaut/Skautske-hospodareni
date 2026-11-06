<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\TaskQueue\EnqueueResult;

/** What happened to e-mails a user sent: how many were queued and how many were left out and why. */
final class PaymentEmailDispatch
{
    /**
     * @param int $alreadyQueued      e-mails left out because the same e-mail already waits in the queue
     * @param int $reminderNotAllowed reminders left out because the payment is not overdue, is closed or got one today
     */
    public function __construct(
        public readonly int $queued,
        public readonly int $alreadyQueued = 0,
        public readonly int $reminderNotAllowed = 0,
    ) {
    }

    public static function fromResult(EnqueueResult $result, int $reminderNotAllowed = 0): self
    {
        return new self($result->queued, $result->alreadyQueued, $reminderNotAllowed);
    }
}
