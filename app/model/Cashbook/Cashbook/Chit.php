<?php

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook;
use Model\Cashbook\ICategory;

class Chit
{

    /** @var int */
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
        ICategory $category
    )
    {
        $this->cashbook = $cashbook;
        $this->update($number, $date, $recipient, $amount, $purpose, $category);
    }

    public function update(?ChitNumber $number, Date $date, ?Recipient $recipient, Amount $amount, string $purpose, ICategory $category): void
    {
        $this->number = $number;
        $this->date = $date;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->category = new Category($category->getId(), $category->getOperationType());
        $this->purpose = $purpose;
    }

    public function lock(int $userId): void
    {
        $this->locked = $userId;
    }

    public function unlock(): void
    {
        $this->locked = NULL;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getCategoryId(): int
    {
        return $this->category->getId();
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function isLocked(): bool
    {
        return $this->locked !== NULL;
    }

}
