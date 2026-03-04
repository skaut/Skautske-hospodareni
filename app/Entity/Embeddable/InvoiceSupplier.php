<?php

declare(strict_types=1);

namespace Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Model\Unit\Unit;

#[Embeddable]
class InvoiceSupplier
{
    #[Column(type: Types::INTEGER)]
    private int $unitId;

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

    public function __construct(int $unitId, string $name, string $address, string $city, string $zipcode, string $companyNumber, string $vatNumber = '', bool $vatPayer = false)
    {
        $this->unitId = $unitId;
        $this->name = $name;
        $this->companyNumber = $companyNumber;
        $this->vatNumber = $vatNumber;
        $this->vatPayer = $vatPayer;
        $this->address = new Address($address, $city, $zipcode);
    }

    public static function fromOfficialUnit(Unit $officialUnit, string $vatNumber, bool $vatPayer): self
    {
        return new self(
            $officialUnit->getId(),
            $officialUnit->getDisplayName(),
            $officialUnit->getStreet(),
            $officialUnit->getCity(),
            $officialUnit->getPostcode(),
            $officialUnit->getIc(),
            $vatNumber,
            $vatPayer
        );
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function setUnitId(int $unitId): void
    {
        $this->unitId = $unitId;
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
