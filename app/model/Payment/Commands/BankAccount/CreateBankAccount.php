<?php

declare(strict_types=1);

namespace Model\Payment\Commands\BankAccount;

use Model\Payment\BankAccount\AccountNumber;

final class CreateBankAccount
{
    /** @var int */
    private $unitId;

    /** @var string */
    private $name;

    /** @var AccountNumber */
    private $number;

    /** @var string|null */
    private $token;

    public function __construct(int $unitId, string $name, AccountNumber $number, ?string $token)
    {
        $this->unitId = $unitId;
        $this->name   = $name;
        $this->number = $number;
        $this->token  = $token;
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
}
