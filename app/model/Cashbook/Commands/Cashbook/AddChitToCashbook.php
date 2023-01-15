<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\DTO\Cashbook\ChitItem as ChitItemDto;

/** @see AddChitToCashbookHandler */
final class AddChitToCashbook
{
    /** @param ChitItemDto[] $items */
    public function __construct(private CashbookId $cashbookId, private ChitBody $body, private PaymentMethod $paymentMethod, private array $items)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getBody(): ChitBody
    {
        return $this->body;
    }

    /** @return ChitItemDto[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }
}
