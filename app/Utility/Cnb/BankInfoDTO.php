<?php

declare(strict_types=1);

namespace Utility\Cnb;

final class BankInfoDTO
{
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        private readonly ?string $bic,
        private readonly ?string $certis,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function getCertis(): ?string
    {
        return $this->certis;
    }

    /**
     * @param array<mixed, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['code'],
            $data['name'],
            $data['bic'] ?? null,
            $data['certis'] ?? null,
        );
    }
}
