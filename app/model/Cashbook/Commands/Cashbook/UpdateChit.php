<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;

/**
 * @see UpdateChitHandler
 */
final class UpdateChit
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var ChitBody */
    private $body;

    /** @var Amount */
    private $amount;

    /** @var int */
    private $categoryId;

    /** @var PaymentMethod */
    private $paymentMethod;

    /** @var string */
    private $purpose;

    public function __construct(
        CashbookId $cashbookId,
        int $chitId,
        ChitBody $body,
        Amount $amount,
        int $categoryId,
        PaymentMethod $paymentMethod,
        string $purpose
    ) {
        $this->cashbookId    = $cashbookId;
        $this->chitId        = $chitId;
        $this->body          = $body;
        $this->amount        = $amount;
        $this->categoryId    = $categoryId;
        $this->paymentMethod = $paymentMethod;
        $this->purpose       = $purpose;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }

    public function getBody() : ChitBody
    {
        return $this->body;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategoryId() : int
    {
        return $this->categoryId;
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }
}
