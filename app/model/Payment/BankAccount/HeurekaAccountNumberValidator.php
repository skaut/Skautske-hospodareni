<?php

namespace Model\Payment\BankAccount;

use BankAccountValidator\Czech;

class HeurekaAccountNumberValidator implements IAccountNumberValidator
{

    /** @var Czech */
    private $heurekaValidator;

    public function __construct(Czech $heurekaValidator)
    {
        $this->heurekaValidator = $heurekaValidator;
    }

    public function validate(?string $prefix, string $number, string $bankCode): bool
    {
        return $this->heurekaValidator->validate([
            $prefix,
            $number,
            $bankCode,
        ]);
    }

}
