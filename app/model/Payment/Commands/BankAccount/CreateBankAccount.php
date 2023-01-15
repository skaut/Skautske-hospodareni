<?php

declare(strict_types=1);

namespace Model\Payment\Commands\BankAccount;

use Model\Payment\BankAccount\AccountNumber;

final class CreateBankAccount
{
    public function __construct(private int $unitId, private string $name, private AccountNumber $number, private string|null $token = null)
    {
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNumber(): AccountNumber
    {
        return $this->number;
    }

    public function getToken(): string|null
    {
        return $this->token;
    }
}
