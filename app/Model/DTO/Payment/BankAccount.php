<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\Embeddable\AccountNumber;
use DateTimeImmutable;

class BankAccount
{
    public function __construct(
        private int $id,
        private int $unitId,
        private string $name,
        private AccountNumber $number,
        private ?string $token,
        private BankTransactionSource $transactionSource,
        private DateTimeImmutable $createdAt,
        private bool $allowedForSubunits,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getTransactionSource(): BankTransactionSource
    {
        return $this->transactionSource;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAllowedForSubunits(): bool
    {
        return $this->allowedForSubunits;
    }
}
