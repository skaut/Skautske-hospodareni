<?php

namespace Model\Payment\BankAccount;


class AccountNumber
{

    /** @var string|NULL */
    private $prefix;

    /** @var string */
    private $number;

    /** @var string */
    private $bankCode;

    public function __construct(?string $prefix, string $number, string $bankCode, IAccountNumberValidator $validator)
    {
        if(!$validator->validate($prefix, $number, $bankCode)) {
            throw new \InvalidArgumentException("Invalid bank account number");
        }
        $this->prefix = $prefix === '' ? NULL : $prefix;
        $this->number = $number;
        $this->bankCode = $bankCode;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getBankCode(): string
    {
        return $this->bankCode;
    }

    public function __toString(): string
    {
        $withoutPrefix = $this->number . "/" . $this->bankCode;

        return $this->prefix !== NULL
            ? $this->prefix . "-" . $withoutPrefix
            : $withoutPrefix;
    }

}
