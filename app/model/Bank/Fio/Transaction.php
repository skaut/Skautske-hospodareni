<?php

namespace Model\Bank\Fio;

use Nette;

class Transaction extends Nette\Object
{

    /** @var int */
    private $id;

    /** @var \DateTime */
    private $date;

    /** @var float */
    private $amount;

    /** @var string */
    private $bankAccount;

    /** @var string */
    private $name;

    /** @var int|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string|NULL */
    private $note;

    public function __construct(
        int $id,
        \DateTime $date,
        float $amount,
        string $bankAccount,
        string $name,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?string $note)
    {
        $this->id = $id;
        $this->date = $date;
        $this->amount = $amount;
        $this->bankAccount = $bankAccount;
        $this->name = $name;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note = $note;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBankAccount(): string
    {
        return $this->bankAccount;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVariableSymbol(): ?int
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

}
