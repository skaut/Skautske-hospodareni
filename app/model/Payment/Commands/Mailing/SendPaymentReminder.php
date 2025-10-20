<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Mailing;

use Model\Event\Handlers\Mailing\SendPaymentReminderHandler;

/** @see SendPaymentReminderHandler */
final class SendPaymentReminder
{
    public function __construct(private int $paymentId, private bool $cli = false)
    {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function isCli(): bool
    {
        return $this->cli;
    }
}
