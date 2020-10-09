<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;

/**
 * @see CashbookScansQueryHandler
 */
final class CashbookScansQuery
{
    private CashbookId $cashbookId;

    private PaymentMethod $paymentMethod;

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
