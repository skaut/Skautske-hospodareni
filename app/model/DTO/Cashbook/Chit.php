<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
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
 * @property-read Category          $category
 * @property-read bool              $locked
 * @property-read CashbookType[]    $inverseCashbookTypes
 */
class Chit
{
    use SmartObject;

    /** @var int */
    private $id;

    /** @var ChitBody */
    private $body;

    /** @var Category */
    private $category;

    /** @var bool */
    private $locked;

    /** @var CashbookType[] */
    private $inverseCashbookTypes;

    /**
     * @param CashbookType[] $inverseCashbookTypes
     */
    public function __construct(int $id, ChitBody $body, Category $category, bool $locked, array $inverseCashbookTypes)
    {
        $this->id                   = $id;
        $this->body                 = $body;
        $this->category             = $category;
        $this->locked               = $locked;
        $this->inverseCashbookTypes = $inverseCashbookTypes;
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

    /**
     * @deprecated use getBody()
     */
    public function getAmount() : Amount
    {
        return $this->body->getAmount();
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
        return $this->category;
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
        return $this->category->getOperationType()->equalsValue(Operation::INCOME);
    }
}
