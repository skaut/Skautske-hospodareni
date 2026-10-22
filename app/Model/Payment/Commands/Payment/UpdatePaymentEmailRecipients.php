<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Payment;

use App\Model\Common\EmailAddress;

final class UpdatePaymentEmailRecipients
{
    /** @param EmailAddress[] $recipients */
    public function __construct(
        private int $paymentId,
        private array $recipients,
    ) {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    /** @return EmailAddress[] */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
