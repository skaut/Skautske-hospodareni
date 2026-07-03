<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\BankAccount;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\Embeddable\AccountNumber;

final class CreateBankAccount
{
    public function __construct(
        private int $unitId,
        private string $name,
        private AccountNumber $number,
        private ?string $token = null,
        private ?BankTransactionSource $transactionSource = null,
    ) {
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getTransactionSource(): ?BankTransactionSource
    {
        return $this->transactionSource;
    }
}
