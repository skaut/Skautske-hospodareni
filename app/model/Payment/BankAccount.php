<?php

namespace Model\Payment;

use Model\Payment\BankAccount\AccountNumber;

class BankAccount
{

    private const FIO_BANK_CODE = '2010';

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var string */
    private $name;

    /** @var AccountNumber */
    private $number;

    /** @var string|NULL */
    private $token;

    /** @var \DateTimeImmutable */
    private $createdAt;

    /** @var bool */
    private $allowedForSubunits = FALSE;

    public function __construct(
        int $unitId,
        string $name,
        AccountNumber $number,
        ?string $token,
        \DateTimeImmutable $createdAt
    )
    {
        $this->unitId = $unitId;
        $this->name = $name;
        $this->number = $number;
        $this->token = $number->getBankCode() === self::FIO_BANK_CODE ? $token : NULL;
        $this->createdAt = $createdAt;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAllowedForSubunits(): bool
    {
        return $this->allowedForSubunits;
    }

}
