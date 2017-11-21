<?php

namespace Model\Cashbook\Commands\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;

final class AddChitToCashbook
{

    /** @var int */
    private $cashbookId;

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
        int $cashbookId,
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        int $categoryId
    )
    {
        $this->cashbookId = $cashbookId;
        $this->number = $number;
        $this->date = $date;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->categoryId = $categoryId;
        $this->purpose = $purpose;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getNumber(): ?ChitNumber
    {
        return $this->number;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

}
