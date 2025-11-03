<?php

declare(strict_types=1);

namespace Utility\Ares;

class ViAresInfo
{
    private ?string $vat = null;

    private ?string $companyName = null;
    private ?string $name = null;
    private ?string $address = null;
    private bool $vatPayer = false;
    private ?string $countryCode = null;

    /** @param array<string, string> $data */
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

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
     * @param array<string, string> $data
     *
     * @return $this
     */
    public function fromArray(array $data): self
    {
        if (isset($data['vat'])) {
            $this->vat = $data['vat'];
        }

        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['address'])) {
            $this->address = $data['address'];
        }

        if (isset($data['vatPayer'])) {
            $this->vatPayer = (bool) $data['vatPayer'];
        }

        if (isset($data['countryCode'])) {
            $this->countryCode = $data['countryCode'];
        }

        return $this;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'vat' => $this->vat,
            'name' => $this->name,
            'address' => $this->address,
            'vatPayer' => $this->vatPayer ? 1 : 0,
            'countryCode' => $this->countryCode,
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
}
