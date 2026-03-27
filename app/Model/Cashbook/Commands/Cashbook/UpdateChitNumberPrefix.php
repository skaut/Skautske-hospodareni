<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Handlers\Cashbook\UpdateChitNumberPrefixHandler;

/** @see UpdateChitNumberPrefixHandler */
final class UpdateChitNumberPrefix
{
    public function __construct(private CashbookId $cashbookId, private PaymentMethod $paymentMethod, private ?string $prefix = null)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
}
