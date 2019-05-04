<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Operation;
use Nette\SmartObject;

/**
 * @property-read int               $id
 * @property-read ChitBody          $body
 * @property-read ChitNumber|NULL   $number
 * @property-read Date              $date
 * @property-read Recipient|NULL    $recipient
 * @property-read Amount            $amount
 * @property-read string            $purpose
 * @property-read ChitItem[]        $items
 * @property-read bool              $locked
 * @property-read CashbookType[]    $inverseCashbookTypes
 * @property-read PaymentMethod     $paymentMethod
 * @property-read Category          $category
 */
class Chit
{
    use SmartObject;

    /** @var int */
    private $id;

    /** @var ChitBody */
    private $body;

    /** @var bool */
    private $locked;

    /** @var CashbookType[] */
    private $inverseCashbookTypes;

    /** @var PaymentMethod */
    private $paymentMethod;

    /** @var ChitItem[] */
    private $items;

    /** @var Operation */
    private $operation;

    /** @var Amount */
    private $amount;

    /**
     * @param CashbookType[] $inverseCashbookTypes
     * @param ChitItem[]     $items
     */
    public function __construct(int $id, ChitBody $body, bool $locked, array $inverseCashbookTypes, PaymentMethod $paymentMethod, array $items, Operation $operation, Amount $amount)
    {
        $this->id                   = $id;
        $this->body                 = $body;
        $this->locked               = $locked;
        $this->inverseCashbookTypes = $inverseCashbookTypes;
        $this->paymentMethod        = $paymentMethod;
        $this->items                = $items;
        $this->operation            = $operation;
        $this->amount               = $amount;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getBody() : ChitBody
    {
        return $this->body;
    }

    /**
     * @deprecated use getBody()
     */
    public function getNumber() : ?ChitNumber
    {
        return $this->body->getNumber();
    }

    /**
     * @deprecated use getBody()
     */
    public function getDate() : Date
    {
        return $this->body->getDate();
    }

    /**
     * @deprecated use getBody()
     */
    public function getRecipient() : ?Recipient
    {
        return $this->body->getRecipient();
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    /**
     * @deprecated use getBody()
     */
    public function getPurpose() : string
    {
        return $this->body->getPurpose();
    }

    public function getCategory() : Category
    {
        return $this->items[0]->getCategory();
    }

    public function isLocked() : bool
    {
        return $this->locked;
    }

    /**
     * @return CashbookType[]
     */
    public function getInverseCashbookTypes() : array
    {
        return $this->inverseCashbookTypes;
    }

    public function isIncome() : bool
    {
        return $this->operation->equalsValue(Operation::INCOME);
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getSignedAmount() : float
    {
        $amount = $this->amount->toFloat();

        if ($this->operation->equalsValue(Operation::EXPENSE)) {
            return -1 * $amount;
        }

        return $amount;
    }

    /**
     * @return ChitItem[]
     */
    public function getItems() : array
    {
        return $this->items;
    }
}
