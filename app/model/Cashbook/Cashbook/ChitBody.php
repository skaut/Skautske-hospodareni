<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
final class ChitBody
{
    /**
     * @var ChitNumber|NULL
     * @ORM\Column(type="chit_number", nullable=true, name="num", length=5)
     */
    private $number;

    /**
     * @var Date
     * @ORM\Column(type="chronos_date")
     */
    private $date;

    /**
     * @var Recipient|NULL
     * @ORM\Column(type="recipient", length=64, nullable=true)
     */
    private $recipient;

    /**
     * @var string
     * @ORM\Column(type="string", length=120)
     */
    private $purpose;

    public function __construct(?ChitNumber $number, Date $date, ?Recipient $recipient, string $purpose)
    {
        $this->number    = $number;
        $this->date      = $date;
        $this->recipient = $recipient;
        $this->purpose   = $purpose;
    }

    public function withoutChitNumber() : self
    {
        return new self(null, $this->date, $this->recipient, $this->purpose);
    }

    public function withNewNumber(ChitNumber $chitNumber) : self
    {
        return new self($chitNumber, $this->date, $this->recipient, $this->purpose);
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

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function equals(ChitBody $other) : bool
    {
        return (string) $other->number ===  (string) $this->number
            && $other->date->eq($this->date)
            && (string) $other->recipient === (string) $this->recipient
            && $other->purpose === $this->purpose;
    }
}
