<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\DTO\Cashbook\ChitItem;

/** @see UpdateChitHandler */
final class UpdateChit
{
    /** @param ChitItem[] $items */
    public function __construct(
        private CashbookId $cashbookId,
        private int $chitId,
        private ChitBody $body,
        private PaymentMethod $paymentMethod,
        private array $items,
    ) {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

    public function getBody(): ChitBody
    {
        return $this->body;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    /** @return ChitItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
