<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

final class MemberEmail
{
    public function __construct(
        private string $address,
        private string $label,
        private MemberEmailType $type,
    ) {
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): MemberEmailType
    {
        return $this->type;
    }
}
