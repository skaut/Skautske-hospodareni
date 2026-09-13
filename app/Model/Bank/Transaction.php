<?php

declare(strict_types=1);

namespace App\Model\Bank;

use App\Model\Bank\Enum\BankTransactionSource;
use DateTimeImmutable;
use Nette\SmartObject;

class Transaction
{
    use SmartObject;

    public function __construct(
        private string $id,
        private BankTransactionSource $source,
        private DateTimeImmutable $date,
        private float $amount,
        private ?string $bankAccount,
        private string $name,
        private ?int $variableSymbol = null,
        private ?int $constantSymbol = null,
        private ?string $note = null,
        private ?string $sourceTransactionId = null,
        private ?string $legacyId = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Klíč, pod kterým už transakce může být uložená z dřívější implementace parseru.
     *
     * Import podle něj transakci dohledá, aby znovunahrání dříve importovaného souboru nevytvořilo
     * duplicity. Ukládá se vždy {@see getId()}; tenhle klíč slouží jen k vyhledání.
     */
    public function getLegacyId(): ?string
    {
        return $this->legacyId;
    }

    /** @return list<string> */
    public function getKnownIds(): array
    {
        return $this->legacyId === null || $this->legacyId === $this->id
            ? [$this->id]
            : [$this->id, $this->legacyId];
    }

    public function getSource(): BankTransactionSource
    {
        return $this->source;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBankAccount(): ?string
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

    public function getSourceTransactionId(): ?string
    {
        return $this->sourceTransactionId;
    }
}
