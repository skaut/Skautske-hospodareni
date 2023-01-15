<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\BankAccount\AccountNumber;

class RepaymentCandidate
{
    public function __construct(
        private int $paymentId,
        private int|null $personId,
        private string $name,
        private float $amount,
        private AccountNumber|null $bankAccount,
    ) {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getPersonId(): int|null
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

    public function getBankAccount(): AccountNumber|null
    {
        return $this->bankAccount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }
}
