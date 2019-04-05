<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;

/**
 * @see GenerateChitNumbersHandler
 */
final class GenerateChitNumbers
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var PaymentMethod */
    private $paymentMethod;

    public function __construct(CashbookId $cashbookId, PaymentMethod $paymentMethod)
    {
        $this->cashbookId    = $cashbookId;
        $this->paymentMethod = $paymentMethod;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }
}
