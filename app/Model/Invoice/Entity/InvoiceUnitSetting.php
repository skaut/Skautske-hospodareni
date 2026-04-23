<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Unit\Unit;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Nette\Utils\ArrayHash;

#[Entity(repositoryClass: \App\Model\Invoice\Repository\InvoiceUnitSettingRepository::class)]
#[Table(name: 'invoice_unit_setting')]
#[UniqueConstraint(name: 'invoice_unit_setting_unit_year_unique', columns: ['unit', 'year'])]
class InvoiceUnitSetting extends AbstractIdEntity
{
    #[Column(type: Types::INTEGER)]
    private int $unit;

    #[Column(type: Types::INTEGER)]
    private int $year;

    #[Column(type: Types::STRING, length: 255)]
    private string $name;

    #[Column(type: Types::STRING, length: 255)]
    private string $street;

    #[Column(type: Types::STRING, length: 64)]
    private string $city;

    #[Column(type: Types::STRING, length: 10)]
    private string $zipcode;

    #[Column(type: Types::STRING, length: 64)]
    private string $companyNumber;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $phone = null;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stampImagePath = null;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoImagePath = null;

    public function __construct(
        int $unit,
        int $year,
        string $name,
        string $street,
        string $city,
        string $zipcode,
        string $companyNumber,
        ?string $phone = null,
        ?string $stampImagePath = null,
        ?string $logoImagePath = null,
    ) {
        $this->unit = $unit;
        $this->year = $year;
        $this->name = $name;
        $this->street = $street;
        $this->city = $city;
        $this->zipcode = $zipcode;
        $this->companyNumber = $companyNumber;
        $this->phone = $phone;
        $this->stampImagePath = $stampImagePath;
        $this->logoImagePath = $logoImagePath;
    }

    public static function fromOfficialUnit(Unit $unit, int $year): self
    {
        return new self(
            $unit->getId(),
            $year,
            $unit->getDisplayName(),
            $unit->getStreet(),
            $unit->getCity(),
            (string) $unit->getPostcode(),
            (string) $unit->getIc(),
        );
    }

    public static function fromForm(int $unitId, ArrayHash $values): self
    {
        return new self(
            $unitId,
            (int) $values->year,
            $values->name,
            $values->street,
            $values->city,
            $values->zipcode,
            $values->companyNumber,
            $values->phone ?: null,
            null,
        );
    }

    public function updateFromForm(ArrayHash $values): void
    {
        $this->year = (int) $values->year;
        $this->name = $values->name;
        $this->street = $values->street;
        $this->city = $values->city;
        $this->zipcode = $values->zipcode;
        $this->companyNumber = $values->companyNumber;
        $this->phone = $values->phone ?: null;
    }

    public function toInvoiceSupplier(): InvoiceSupplier
    {
        return InvoiceSupplier::fromUnitSetting($this);
    }

    /** @return array<string, mixed> */
    public function toFormValues(): array
    {
        return [
            'year' => $this->year,
            'name' => $this->name,
            'street' => $this->street,
            'city' => $this->city,
            'zipcode' => $this->zipcode,
            'companyNumber' => $this->companyNumber,
            'phone' => $this->phone,
        ];
    }

    public function getUnit(): int
    {
        return $this->unit;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    public function getCompanyNumber(): string
    {
        return $this->companyNumber;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getStampImagePath(): ?string
    {
        return $this->stampImagePath;
    }

    public function setStampImagePath(?string $stampImagePath): void
    {
        $this->stampImagePath = $stampImagePath;
    }

    public function getLogoImagePath(): ?string
    {
        return $this->logoImagePath;
    }

    public function setLogoImagePath(?string $logoImagePath): void
    {
        $this->logoImagePath = $logoImagePath;
    }
}
