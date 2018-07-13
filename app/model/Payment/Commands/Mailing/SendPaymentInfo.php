<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Mailing;

use Model\Event\Handlers\Mailing\SendPaymentInfoHandler;

/**
 * @see SendPaymentInfoHandler
 */
final class SendPaymentInfo
{
    /** @var int */
    private $paymentId;

    public function __construct(int $paymentId)
    {
        $this->paymentId = $paymentId;
    }

    public function getPaymentId() : int
    {
        return $this->paymentId;
    }
}
