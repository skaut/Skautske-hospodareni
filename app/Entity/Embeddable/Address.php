<?php

declare(strict_types=1);

namespace Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

#[Embeddable]
class Address
{
    #[Column(type: Types::STRING, length: 255, nullable: false)]
    private string $street;

    #[Column(type: Types::STRING, length: 64, nullable: false)]
    private string $city;

    #[Column(type: Types::STRING, length: 10, nullable: false)]
    private string $zipCode;

    #[Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $streetNumber = null;

    #[Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $streetNumberSuffix = null;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $countryName = null;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $countryCode = null;

    public function __construct(string $street, string $city, string $zipCode, ?string $streetNumber = null, ?string $streetNumberSuffix = null)
    {
        $this->street = $street;
        $this->city = $city;
        $this->zipCode = $zipCode;
        $this->streetNumber = $streetNumber;
        $this->streetNumberSuffix = $streetNumberSuffix;
    }

    private function getAddress(): string
    {
        return sprintf('%s %s/%s', $this->street, $this->streetNumber, $this->streetNumberSuffix);
    }

    public function getFullAddress(): string
    {
        return sprintf('%s, %s %s', $this->getAddress(), $this->city, $this->zipCode);
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(?string $streetNumber): void
    {
        $this->streetNumber = $streetNumber;
    }

    public function getStreetNumberSuffix(): ?string
    {
        return $this->streetNumberSuffix;
    }

    public function setStreetNumberSuffix(?string $streetNumberSuffix): void
    {
        $this->streetNumberSuffix = $streetNumberSuffix;
    }

    public function getCountryName(): string
    {
        return $this->countryName;
    }

    public function setCountryName(string $countryName): void
    {
        $this->countryName = $countryName;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }
}
