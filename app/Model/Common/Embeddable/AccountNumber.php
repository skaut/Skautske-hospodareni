<?php

declare(strict_types=1);

namespace App\Model\Common\Embeddable;

use App\Model\Payment\InvalidBankAccountNumber;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Utility\Cnb\BankAccountValidator;

#[Embeddable]
class AccountNumber
{
    #[Column(type: 'string', length: 6, nullable: true)]
    private ?string $prefix = null;

    #[Column(type: 'string', length: 10, nullable: true)]
    private ?string $number = null;

    #[Column(type: 'string', length: 4, nullable: true)]
    private ?string $bankCode = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $bankName = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $iban = null;
    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $bic = null;

    /**
     * @throws InvalidBankAccountNumber
     */
    public function __construct(?string $prefix, string $number, string $bankCode, ?string $bankName = null, ?string $iban = null, ?string $bic = null)
    {
        if (! self::validateParts($prefix, $number, $bankCode)) {
            throw self::invalidNumber();
        }

        $this->prefix = $prefix === '' ? null : $prefix;
        $this->number = $number;
        $this->bankCode = $bankCode;
        $this->bankName = $bankName;
        $this->iban = $iban;
        $this->bic = $bic;
    }

    /**
     * @throws InvalidBankAccountNumber
     */
    public static function fromString(string $number): self
    {
        $parser = new BankAccountValidator();
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

    public static function validateParts(?string $prefix, string $number, string $bankCode): bool
    {
        $validator = new BankAccountValidator();

        return $validator->validate([$prefix, $number, $bankCode]);
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getNumber(): string
    {
        return $this->number ?? '';
    }

    public function getNumberWithPrefix(): string
    {
        if ($this->prefix !== null) {
            return $this->prefix.'-'.$this->getNumber();
        }

        return $this->getNumber();
    }

    public function getNumberWithPrefixAndBankCode(): string
    {
        return $this->getNumberWithPrefix().'/'.$this->getBankCode();
    }

    public function getBankCode(): string
    {
        return $this->bankCode ?? '';
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): void
    {
        $this->bic = $bic;
    }

    public function __toString(): string
    {
        $withoutPrefix = $this->getNumber().'/'.$this->getBankCode();

        return $this->prefix !== null
            ? $this->prefix.'-'.$withoutPrefix
            : $withoutPrefix;
    }

    private static function invalidNumber(): InvalidBankAccountNumber
    {
        return new InvalidBankAccountNumber('Invalid bank account number');
    }
}
