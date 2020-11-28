<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

class RepaymentCandidate
{
    private int $personId;

    private string $name;

    private float $amount;

    private ?string $bankAccount;

    public function __construct(
        int $personId,
        string $name,
        float $amount,
        ?string $bankAccount
    ) {
        $this->personId    = $personId;
        $this->name        = $name;
        $this->amount      = $amount;
        $this->bankAccount = $bankAccount;
    }

    public function getPersonId() : int
    {
        return $this->personId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function getBankAccount() : ?string
    {
        return $this->bankAccount;
    }

    public function setAmount(float $amount) : void
    {
        $this->amount = $amount;
    }
}
