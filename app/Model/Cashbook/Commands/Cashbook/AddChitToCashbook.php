<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\DTO\Cashbook\ChitItem as ChitItemDto;

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
