<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Category as CategoryAggregate;
use Model\Cashbook\Operation;

class Chit
{
    /** @var int|NULL */
    private $id;

    /** @var Cashbook */
    private $cashbook;

    /** @var ChitNumber|NULL */
    private $number;

    /** @var Date */
    private $date;

    /** @var Recipient|NULL */
    private $recipient;

    /** @var Amount */
    private $amount;

    /** @var string */
    private $purpose;

    /** @var Category */
    private $category;

    /**
     * ID of person that locked this
     *
     * @var int|NULL
     */
    private $locked;

    public function __construct(
        Cashbook $cashbook,
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        Category $category
    ) {
        $this->cashbook = $cashbook;
        $this->update($number, $date, $recipient, $amount, $purpose, $category);
    }

    public function update(?ChitNumber $number, Date $date, ?Recipient $recipient, Amount $amount, string $purpose, Category $category) : void
    {
        $this->number    = $number;
        $this->date      = $date;
        $this->recipient = $recipient;
        $this->amount    = $amount;
        $this->category  = $category;
        $this->purpose   = $purpose;
    }

    public function lock(int $userId) : void
    {
        $this->locked = $userId;
    }

    public function unlock() : void
    {
        $this->locked = null;
    }

    public function getId() : int
    {
        if ($this->id === null) {
            throw new \RuntimeException('ID not set');
        }

        return $this->id;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategoryId() : int
    {
        return $this->category->getId();
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function isLocked() : bool
    {
        return $this->locked !== null;
    }

    public function getNumber() : ?ChitNumber
    {
        return $this->number;
    }

    public function getDate() : Date
    {
        return $this->date;
    }

    public function getRecipient() : ?Recipient
    {
        return $this->recipient;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }

    public function copyToCashbook(Cashbook $newCashbook) : self
    {
        $newChit           = clone $this;
        $newChit->id       = null;
        $newChit->cashbook = $newCashbook;

        return $newChit;
    }

    public function copyToCashbookWithUndefinedCategory(Cashbook $newCashbook) : self
    {
        $newChit   = $this->copyToCashbook($newCashbook);
        $operation = $newChit->category->getOperationType();

        $newChit->category = new Category(
            $operation->equalsValue(Operation::INCOME)
                ? CategoryAggregate::UNDEFINED_INCOME_ID
                : CategoryAggregate::UNDEFINED_EXPENSE_ID,
            $operation
        );

        return $newChit;
    }
}
