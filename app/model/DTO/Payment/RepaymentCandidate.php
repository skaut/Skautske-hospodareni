<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\BankAccount\AccountNumber;

class RepaymentCandidate
{
    private int $personId;

    private string $name;

    private float $amount;

    private ?AccountNumber $bankAccount;

    public function __construct(
        int $personId,
        string $name,
        float $amount,
        ?AccountNumber $bankAccount
    ) {
        $this->personId    = $personId;
        $this->name        = $name;
        $this->amount      = $amount;
        $this->bankAccount = $bankAccount;
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBankAccount(): ?AccountNumber
    {
        return $this->bankAccount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }
}
