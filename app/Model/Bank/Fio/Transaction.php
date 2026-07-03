<?php

declare(strict_types=1);

namespace App\Model\Bank\Fio;

use DateTimeImmutable;
use Nette;

/**
 * @property string            $id
 * @property DateTimeImmutable $date
 * @property float             $amount
 * @property string            $bankAccount
 * @property string            $name
 * @property int|null          $variableSymbol
 * @property int|null          $constantSymbol
 * @property string|null       $note
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
        private ?int $variableSymbol = null,
        private ?int $constantSymbol = null,
        private ?string $note = null,
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
