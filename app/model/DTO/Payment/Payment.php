<?php

namespace Model\DTO\Payment;

use DateTimeImmutable;

class Payment
{

    /** @var string */
    private $name;

    /** @var float */
    private $amount;

    /** @var string|NULL */
    private $email;

    /** @var DateTimeImmutable */
    private $dueDate;

    /** @var int|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string */
    private $note;

    /**
     * Payment constructor.
     * @param string $name
     * @param float $amount
     * @param string|NULL $email
     * @param DateTimeImmutable $dueDate
     * @param int|NULL $variableSymbol
     * @param int|NULL $constantSymbol
     * @param string $note
     */
    public function __construct(
        string $name,
        float $amount,
        ?string $email,
        DateTimeImmutable $dueDate,
        ?int $variableSymbol,
        $constantSymbol,
        $note
    )
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->email = $email;
        $this->dueDate = $dueDate;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note = $note;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return NULL|string
     */
    public function getEmail() : ?string
    {
        return $this->email;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * @return int|NULL
     */
    public function getVariableSymbol() : ?int
    {
        return $this->variableSymbol;
    }

    /**
     * @return int|NULL
     */
    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

}