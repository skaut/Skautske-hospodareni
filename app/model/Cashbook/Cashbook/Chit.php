<?php

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook;

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

    /** @var int */
    private $categoryId;

    public function __construct(
        Cashbook $cashbook,
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        int $categoryId
    )
    {
        $this->cashbook = $cashbook;
        $this->update($number, $date, $recipient, $amount, $purpose, $categoryId);
    }

    public function update(?ChitNumber $number, Date $date, ?Recipient $recipient, Amount $amount, string $purpose, int $categoryId): void
    {
        $this->number = $number;
        $this->date = $date;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->categoryId = $categoryId;
        $this->purpose = $purpose;
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
        return $this->categoryId;
    }

}
