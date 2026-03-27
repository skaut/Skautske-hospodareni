<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Mailing;

use App\Model\Event\Handlers\Mailing\SendPaymentInfoHandler;

/** @see SendPaymentInfoHandler */
final class SendPaymentInfo
{
    public function __construct(private int $paymentId)
    {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }
}
