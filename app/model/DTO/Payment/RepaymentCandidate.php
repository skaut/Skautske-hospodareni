<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Entity\Embeddable\AccountNumber;

class RepaymentCandidate
{
    public function __construct(
        private int $paymentId,
        private ?int $personId,
        private string $name,
        private float $amount,
        private ?AccountNumber $bankAccount,
    ) {
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
