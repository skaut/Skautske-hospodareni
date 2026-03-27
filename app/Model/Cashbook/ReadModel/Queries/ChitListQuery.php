<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\QueryHandlers\ChitListQueryHandler;

/** @see ChitListQueryHandler */
final class ChitListQuery
{
    /**
     * Use static factory method.
     */
    private function __construct(private CashbookId $cashbookId, private ?PaymentMethod $paymentMethod = null)
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

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }
}
