<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;

final class ChitBody
{
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

    public function __construct(?ChitNumber $number, Date $date, ?Recipient $recipient, Amount $amount, string $purpose)
    {
        $this->number    = $number;
        $this->date      = $date;
        $this->recipient = $recipient;
        $this->amount    = $amount;
        $this->purpose   = $purpose;
    }

    public function withoutChitNumber() : self
    {
        return new self(null, $this->date, $this->recipient, $this->amount, $this->purpose);
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

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function equals(ChitBody $other) : bool
    {
        return (string) $other->number ===  (string) $this->number
            && $other->date->eq($this->date)
            && (string) $other->recipient === (string) $this->recipient
            && $other->amount->getExpression() === $this->amount->getExpression()
            && $other->purpose === $this->purpose;
    }
}
