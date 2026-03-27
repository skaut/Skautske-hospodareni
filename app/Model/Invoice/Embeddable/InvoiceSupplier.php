<?php

declare(strict_types=1);

namespace App\Model\Invoice\Embeddable;

use App\Model\Common\Embeddable\Address;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Unit\Unit;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;

#[Embeddable]
class InvoiceSupplier
{
    #[Column(type: Types::INTEGER)]
    private int $unitId;

    #[Column(type: Types::STRING, length: 255)]
    private string $name;

    #[Column(type: Types::STRING, length: 64)]
    private string $companyNumber;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $phone = null;

    #[Embedded(class: Address::class)]
    private Address $address;

    public function __construct(int $unitId, string $name, string $address, string $city, string $zipcode, string $companyNumber, ?string $phone = null)
    {
        $this->unitId = $unitId;
        $this->name = $name;
        $this->companyNumber = $companyNumber;
        $this->phone = $phone;
        $this->address = new Address($address, $city, $zipcode);
    }

    public static function fromOfficialUnit(Unit $officialUnit, ?string $phone = null): self
    {
        return new self(
            $officialUnit->getId(),
            $officialUnit->getDisplayName(),
            $officialUnit->getStreet(),
            $officialUnit->getCity(),
            $officialUnit->getPostcode(),
            $officialUnit->getIc(),
            $phone,
        );
    }

    public static function fromUnitSetting(InvoiceUnitSetting $setting): self
    {
        return new self(
            $setting->getUnit(),
            $setting->getName(),
            $setting->getStreet(),
            $setting->getCity(),
            $setting->getZipcode(),
            $setting->getCompanyNumber(),
            $setting->getPhone(),
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

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }
}
