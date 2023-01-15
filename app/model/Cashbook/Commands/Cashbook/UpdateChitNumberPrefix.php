<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Handlers\Cashbook\UpdateChitNumberPrefixHandler;

/** @see UpdateChitNumberPrefixHandler */
final class UpdateChitNumberPrefix
{
    public function __construct(private CashbookId $cashbookId, private PaymentMethod $paymentMethod, private string|null $prefix = null)
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

    public function getPrefix(): string|null
    {
        return $this->prefix;
    }
}
