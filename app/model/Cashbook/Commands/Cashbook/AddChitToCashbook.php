<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;

/**
 * @see AddChitToCashbookHandler
 */
final class AddChitToCashbook
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var ChitBody */
    private $body;

    /** @var int */
    private $categoryId;

    /** @var PaymentMethod */
    private $paymentMethod;

    public function __construct(CashbookId $cashbookId, ChitBody $body, int $categoryId, PaymentMethod $paymentMethod)
    {
        $this->cashbookId    = $cashbookId;
        $this->body          = $body;
        $this->categoryId    = $categoryId;
        $this->paymentMethod = $paymentMethod;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getBody() : ChitBody
    {
        return $this->body;
    }

    public function getCategoryId() : int
    {
        return $this->categoryId;
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }
}
