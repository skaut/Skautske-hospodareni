<?php

declare(strict_types=1);

namespace Model\Bank\Fio;

use DateTimeImmutable;
use Nette;

/**
 * @property-read string                $id
 * @property-read DateTimeImmutable $date
 * @property-read float                 $amount
 * @property-read string                $bankAccount
 * @property-read string                $name
 * @property-read int|NULL              $variableSymbol
 * @property-read int|NULL              $constantSymbol
 * @property-read string|NULL           $note
 */
class Transaction
{
    use Nette\SmartObject;

    public function __construct(
        private string $id,
        private DateTimeImmutable $date,
        private float $amount,
        private string $bankAccount,
        private string $name,
        private int|null $variableSymbol = null,
        private int|null $constantSymbol = null,
        private string|null $note = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDate(): DateTimeImmutable
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

    public function getVariableSymbol(): int|null
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol(): int|null
    {
        return $this->constantSymbol;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }
}
