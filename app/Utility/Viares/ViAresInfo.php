<?php

declare(strict_types=1);

namespace Utility\Ares;

class ViAresInfo
{
    private ?string $vat = null;

    private ?string $companyName = null;
    private ?string $name = null;
    private ?string $street = null;
    private ?string $streetNumber = null;
    private ?string $streetNumberSuffix = null;
    private ?string $city = null;
    private ?string $zipCode = null;

    private bool $vatPayer = false;
    private ?string $countryCode = null;

    /** @param array<string, int|string|null> $data */
    public function __construct(array $data = [])
    {
        if (empty($data)) {
            return;
        }

        $this->fromArray($data);
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }

    public function setVat(?string $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function isVatPayer(): bool
    {
        return $this->vatPayer;
    }

    public function setVatPayer(bool $vatPayer): self
    {
        $this->vatPayer = $vatPayer;

        return $this;
    }

    /**
     * @param array<string, int|string|null> $data
     *
     * @return $this
     */
    public function fromArray(array $data): self
    {
        if (isset($data['vat'])) {
            $this->vat = (string) $data['vat'];
        }

        if (isset($data['name'])) {
            $this->name = (string) $data['name'];
        }

        if (isset($data['companyName'])) {
            $this->companyName = (string) $data['companyName'];
        }

        if (isset($data['street'])) {
            $this->street = (string) $data['street'];
        }

        if (isset($data['streetNumber'])) {
            $this->streetNumber = (string) $data['streetNumber'];
        }

        if (isset($data['streetNumberSuffix'])) {
            $this->streetNumberSuffix = (string) $data['streetNumberSuffix'];
        }

        if (isset($data['vatPayer'])) {
            $this->vatPayer = (bool) $data['vatPayer'];
        }

        if (isset($data['countryCode'])) {
            $this->countryCode = (string) $data['countryCode'];
        }

        return $this;
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'companyName' => $this->getCompanyName(),
            'vat' => $this->getVat(),
            'name' => $this->getName(),
            'street' => $this->getStreet(),
            'streetNumber' => $this->getStreetNumber(),
            'streetNumberSuffix' => $this->getStreetNumberSuffix(),
            'city' => $this->getCity(),
            'zipCode' => $this->getZipCode(),
            'vatPayer' => $this->vatPayer ? 1 : 0,
            'countryCode' => $this->getCountryCode(),
            'address' => $this->getAddress(),
            'fullAddress' => $this->getFullAddress(),
        ];
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getStreetNumberSuffix(): ?string
    {
        return $this->streetNumberSuffix;
    }

    public function setStreetNumberSuffix(?string $streetNumberSuffix): self
    {
        $this->streetNumberSuffix = $streetNumberSuffix;

        return $this;
    }

    private function getAddress(): string
    {
        return sprintf('%s %s/%s', $this->street, $this->streetNumber, $this->streetNumberSuffix);
    }

    public function getFullAddress(): string
    {
        return sprintf('%s, %s %s', $this->getAddress(), $this->city, $this->zipCode);
    }

    public function isEmpty(): bool
    {
        return $this->vat === null
            && $this->name === null
            && $this->street === null
            && $this->streetNumber === null
            && $this->streetNumberSuffix === null
            && $this->city === null
            && $this->zipCode === null;
    }
}
