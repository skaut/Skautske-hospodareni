<?php

declare(strict_types=1);

namespace Model\Invoice;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_unit')]
class InvoiceUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'integer')]
    private int $unitId = 0;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $address = '';

    #[ORM\Column(type: 'string', length: 64)]
    private string $city = '';

    #[ORM\Column(type: 'string', length: 64)]
    private string $zipcode = '';

    #[ORM\Column(type: 'string', length: 64)]
    private string $companyNumber = '';

    #[ORM\Column(type: 'string', length: 64)]
    private string $vatNumber = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $registration = '';

    #[ORM\Column(type: 'boolean')]
    private bool $vatPayer = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
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

    public function getVatNumber(): string
    {
        return $this->vatNumber;
    }

    public function getRegistration(): string
    {
        return $this->registration;
    }

    public function isVatPayer(): bool
    {
        return $this->vatPayer;
    }
}
