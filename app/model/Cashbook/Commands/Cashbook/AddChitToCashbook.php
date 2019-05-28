<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\DTO\Cashbook\ChitItem as ChitItemDto;

/**
 * @see AddChitToCashbookHandler
 */
final class AddChitToCashbook
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var ChitBody */
    private $body;

    /** @var ChitItemDto[] */
    private $items;

    /** @var PaymentMethod */
    private $paymentMethod;

    /**
     * @param ChitItemDto[] $items
     */
    public function __construct(CashbookId $cashbookId, ChitBody $body, PaymentMethod $paymentMethod, array $items)
    {
        $this->cashbookId    = $cashbookId;
        $this->body          = $body;
        $this->paymentMethod = $paymentMethod;
        $this->items         = $items;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getBody() : ChitBody
    {
        return $this->body;
    }

    /**
     * @return ChitItemDto[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }
}
