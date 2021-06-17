<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\BankAccount\AccountNumber;

class RepaymentCandidate
{
    private int $paymentId;

    private ?int $personId;

    private string $name;

    private float $amount;

    private ?AccountNumber $bankAccount;

    public function __construct(
        int $paymentId,
        ?int $personId,
        string $name,
        float $amount,
        ?AccountNumber $bankAccount
    ) {
        $this->paymentId   = $paymentId;
        $this->personId    = $personId;
        $this->name        = $name;
        $this->amount      = $amount;
        $this->bankAccount = $bankAccount;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getPersonId(): ?int
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
