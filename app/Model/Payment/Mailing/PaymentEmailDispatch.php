<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\TaskQueue\EnqueueResult;

/**
 * What happened to e-mails a user sent: how many were queued and which were left out and why. Only e-mails
 * that can go out are queued; one that would surely fail is left out and the user is told right away.
 */
final class PaymentEmailDispatch
{
    public const NO_RECIPIENT = 'no_recipient';
    public const PAYMENT_CLOSED = 'payment_closed';
    public const REMINDER_NOT_ALLOWED = 'reminder_not_allowed';

    /**
     * @param int                $alreadyQueued e-mails left out because the same e-mail already waits in the queue
     * @param array<string, int> $leftOut       reason (one of the constants) => payments left out for it
     * @param string|null        $blocked       why no e-mail of the group can go out at all
     */
    public function __construct(
        public readonly int $queued,
        public readonly int $alreadyQueued = 0,
        public readonly array $leftOut = [],
        public readonly ?string $blocked = null,
    ) {
    }

    /** @param array<string, int> $leftOut */
    public static function fromResult(EnqueueResult $result, array $leftOut = []): self
    {
        return new self($result->queued, $result->alreadyQueued, $leftOut);
    }

    public static function blocked(string $reason): self
    {
        return new self(0, blocked: $reason);
    }
}
