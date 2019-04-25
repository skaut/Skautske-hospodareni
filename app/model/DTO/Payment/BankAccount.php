<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use DateTimeImmutable;
use Model\Payment\BankAccount\AccountNumber;

class BankAccount
{
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

    /** @var DateTimeImmutable */
    private $createdAt;

    /** @var bool */
    private $allowedForSubunits;

    public function __construct(
        int $id,
        int $unitId,
        string $name,
        AccountNumber $number,
        ?string $token,
        DateTimeImmutable $createdAt,
        bool $allowedForSubunits
    ) {
        $this->id                 = $id;
        $this->unitId             = $unitId;
        $this->name               = $name;
        $this->number             = $number;
        $this->token              = $token;
        $this->createdAt          = $createdAt;
        $this->allowedForSubunits = $allowedForSubunits;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getNumber() : AccountNumber
    {
        return $this->number;
    }

    public function getToken() : ?string
    {
        return $this->token;
    }

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAllowedForSubunits() : bool
    {
        return $this->allowedForSubunits;
    }
}
