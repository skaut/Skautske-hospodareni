<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\QueryHandlers\ChitListQueryHandler;

/** @see ChitListQueryHandler */
final class ChitListQuery
{
    /**
     * Use static factory method
     */
    private function __construct(private CashbookId $cashbookId, private PaymentMethod|null $paymentMethod = null)
    {
    }

    public static function withMethod(PaymentMethod $paymentMethod, CashbookId $cashbookId): self
    {
        return new self($cashbookId, $paymentMethod);
    }

    public static function all(CashbookId $cashbookId): self
    {
        return new self($cashbookId, null);
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getPaymentMethod(): PaymentMethod|null
    {
        return $this->paymentMethod;
    }
}
