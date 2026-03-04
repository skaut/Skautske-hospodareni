<?php

declare(strict_types=1);

namespace Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Nette\Utils\ArrayHash;

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
            $customer['name'],
            $customer['street'],
            $customer['city'],
            $customer['zipCode'],
            $customer['streetNumber'] ?? '',
            $customer['streetNumberSuffix'] ?? '',
            $customer['companyNumber'],
            $customer['vat'] ?? '',
            ! empty($customer['vat']),
        );
    }

    public function getName(): string
    {
        return $this->name;
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

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }
}
