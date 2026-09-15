<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Common\Embeddable\AccountNumber;
use Money\Money;

class RepaymentCandidate
{
    public function __construct(
        private int $paymentId,
        private ?int $personId,
        private string $name,
        private Money $amount,
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

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getBankAccount(): ?AccountNumber
    {
        return $this->bankAccount;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount;
    }
}
