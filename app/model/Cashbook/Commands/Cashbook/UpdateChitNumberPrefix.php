<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Handlers\Cashbook\UpdateChitNumberPrefixHandler;

/**
 * @see UpdateChitNumberPrefixHandler
 */
final class UpdateChitNumberPrefix
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var PaymentMethod */
    private $paymentMethod;

    /** @var string|NULL */
    private $prefix;

    public function __construct(CashbookId $cashbookId, PaymentMethod $paymentMethod, ?string $prefix)
    {
        $this->cashbookId    = $cashbookId;
        $this->paymentMethod = $paymentMethod;
        $this->prefix        = $prefix;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPrefix() : ?string
    {
        return $this->prefix;
    }
}
