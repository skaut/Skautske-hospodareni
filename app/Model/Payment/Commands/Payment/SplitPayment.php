<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Payment;

use App\Model\Payment\Handlers\Payment\SplitPaymentHandler;

/** @see SplitPaymentHandler */
final class SplitPayment
{
    /** @param list<SplitPaymentPart> $parts */
    public function __construct(private int $paymentId, private array $parts)
    {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    /** @return list<SplitPaymentPart> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
