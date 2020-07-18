<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\DTO\Cashbook\ChitItem;

/**
 * @see UpdateChitHandler
 */
final class UpdateChit
{
    private CashbookId $cashbookId;

    private int $chitId;

    private ChitBody $body;

    private PaymentMethod $paymentMethod;

    /** @var ChitItem[] */
    private array $items;

    /**
     * @param ChitItem[] $items
     */
    public function __construct(
        CashbookId $cashbookId,
        int $chitId,
        ChitBody $body,
        PaymentMethod $paymentMethod,
        array $items
    ) {
        $this->cashbookId    = $cashbookId;
        $this->chitId        = $chitId;
        $this->body          = $body;
        $this->paymentMethod = $paymentMethod;
        $this->items         = $items;
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

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @return ChitItem[]
     */
    public function getItems() : array
    {
        return $this->items;
    }
}
