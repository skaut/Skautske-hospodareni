<?php

declare(strict_types=1);

namespace App\Model\Invoice\Embeddable;

use App\Model\Common\Embeddable\Address;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Nette\Utils\ArrayHash;

use function array_filter;
use function implode;
use function trim;

#[Embeddable]
class InvoiceCustomer
{
    #[Column(type: Types::STRING, length: 255)]
    private string $name;

    #[Column(type: Types::STRING, length: 64)]
    private string $companyNumber;

    #[Column(type: Types::STRING, length: 64)]
    private string $vatNumber;

    #[Column(type: Types::BOOLEAN)]
    private bool $vatPayer;

    #[Embedded(class: Address::class)]
    private Address $address;

    public function __construct(string $name, string $street, string $city, string $zipCode, string $streetNumber, string $streetNumberSuffix, string $companyNumber, string $vatNumber = '', bool $vatPayer = false)
    {
        $this->name = $name;
        $this->companyNumber = $companyNumber;
        $this->vatNumber = $vatNumber;
        $this->vatPayer = $vatPayer;
        $this->address = new Address($street, $city, $zipCode, $streetNumber, $streetNumberSuffix);
    }

    /**
     * @param array<string, mixed>|ArrayHash<string, mixed> $customer
     */
    public static function fromForm(array|ArrayHash $customer): InvoiceCustomer
    {
        return new self(
            (string) ($customer['name'] ?? ''),
            (string) ($customer['street'] ?? ''),
            (string) ($customer['city'] ?? ''),
            (string) ($customer['zipCode'] ?? ''),
            $customer['streetNumber'] ?? '',
            $customer['streetNumberSuffix'] ?? '',
            (string) ($customer['companyNumber'] ?? ''),
            $customer['vat'] ?? '',
            ! empty($customer['vat']),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->isAnonymous() ? 'Bez identifikace odběratele' : $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCompanyNumber(): string
    {
        return $this->companyNumber;
    }

    public function setCompanyNumber(string $companyNumber): void
    {
        $this->companyNumber = $companyNumber;
    }

    public function getVatNumber(): string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(string $vatNumber): void
    {
        $this->vatNumber = $vatNumber;
    }

    public function isVatPayer(): bool
    {
        return $this->vatPayer;
    }

    public function setVatPayer(bool $vatPayer): void
    {
        $this->vatPayer = $vatPayer;
    }

    public function hasCompanyNumber(): bool
    {
        return trim($this->companyNumber) !== '';
    }

    public function hasVatNumber(): bool
    {
        return trim($this->vatNumber) !== '';
    }

    public function hasAddress(): bool
    {
        return trim($this->address->getStreet()) !== ''
            || trim($this->address->getCity()) !== ''
            || trim($this->address->getZipCode()) !== '';
    }

    public function isAnonymous(): bool
    {
        return trim($this->name) === ''
            && ! $this->hasCompanyNumber()
            && ! $this->hasVatNumber()
            && ! $this->hasAddress();
    }

    public function getDisplayAddress(): string
    {
        $streetNumber = trim((string) $this->address->getStreetNumber());
        $streetNumberSuffix = trim((string) $this->address->getStreetNumberSuffix());
        $streetLine = trim($this->address->getStreet());

        if ($streetNumber !== '' || $streetNumberSuffix !== '') {
            $streetLine = trim($streetLine.' '.$streetNumber.($streetNumberSuffix !== '' ? '/'.$streetNumberSuffix : ''));
        }

        $cityLine = trim($this->address->getZipCode().' '.$this->address->getCity());

        return implode(', ', array_filter([$streetLine, $cityLine], static fn (string $line): bool => $line !== ''));
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }
}
