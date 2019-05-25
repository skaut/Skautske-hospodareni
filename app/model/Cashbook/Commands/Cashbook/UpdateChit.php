<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use App\AccountancyModule\Components\Cashbook\Form\ChitItem;
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

    /** @var PaymentMethod */
    private $paymentMethod;

    /** @var ChitItem[] */
    private $items;

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

    public function getItems() : array
    {
        return $this->items;
    }
}
