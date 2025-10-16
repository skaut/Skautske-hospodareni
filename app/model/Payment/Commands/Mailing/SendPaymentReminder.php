<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Mailing;

use Model\Event\Handlers\Mailing\SendPaymentReminderHandler;

/** @see SendPaymentReminderHandler */
final class SendPaymentReminder
{
    public function __construct(private int $paymentId)
    {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }
}
