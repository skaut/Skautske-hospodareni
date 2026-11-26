<?php

declare(strict_types=1);

namespace App\Model\Payment\Subscribers;

use App\Model\Payment\DomainEvents\PaymentWasCompleted;
use App\Model\Payment\Mailing\PaymentEmailQueue;

final class PaymentMailSubscriber
{
    public function __construct(private PaymentEmailQueue $emailQueue)
    {
    }

    /**
     * The confirmation e-mail is only queued here, in the transaction that completes the payment. A slow
     * or broken mail setup of the group therefore never holds up or rolls back the payment, and a payment
     * whose completion is rolled back sends nothing.
     */
    public function __invoke(PaymentWasCompleted $event): void
    {
        $this->emailQueue->queueCompletedEmail($event->getId());
    }
}
