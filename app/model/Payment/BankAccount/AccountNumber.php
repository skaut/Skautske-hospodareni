<?php

declare(strict_types=1);

namespace Model\Payment\BankAccount;

use BankAccountValidator\Czech;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\InvalidBankAccountNumber;

/** @ORM\Embeddable() */
class AccountNumber
{
    /** @ORM\Column(type="string", nullable=true, length=6) */
    private string|null $prefix = null;

    /** @ORM\Column(type="string", length=10) */
    private string $number;

    /** @ORM\Column(type="string", length=4) */
    private string $bankCode;

    /** @throws InvalidBankAccountNumber */
    public function __construct(string|null $prefix, string $number, string $bankCode)
    {
        $validator = new Czech();

        if (! $validator->validate([$prefix, $number, $bankCode])) {
            throw self::invalidNumber();
        }

        $this->prefix   = $prefix === '' ? null : $prefix;
        $this->number   = $number;
        $this->bankCode = $bankCode;
    }

    /** @throws InvalidBankAccountNumber */
    public static function fromString(string $number): self
    {
        $parser = new Czech();
        $number = $parser->parseNumber($number);

        if ($number[1] === null || $number[2] === null) {
            throw self::invalidNumber();
        }

        return new self(...$number);
    }

    public static function isValid(string $number): bool
    {
        try {
            self::fromString($number);

            return true;
        } catch (InvalidBankAccountNumber) {
            return false;
        }
    }

    public function getPrefix(): string|null
    {
        return $this->prefix;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getNumberWithPrefix(): string
    {
        if ($this->prefix !== null) {
            return $this->prefix . '-' . $this->number;
        }

        return $this->number;
    }

    public function getBankCode(): string
    {
        return $this->bankCode;
    }

    public function __toString(): string
    {
        $withoutPrefix = $this->number . '/' . $this->bankCode;

        return $this->prefix !== null
            ? $this->prefix . '-' . $withoutPrefix
            : $withoutPrefix;
    }

    private static function invalidNumber(): InvalidBankAccountNumber
    {
        return new InvalidBankAccountNumber('Invalid bank account number');
    }
}
