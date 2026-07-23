<?php

declare(strict_types=1);

namespace App\Model\Grant;

use LogicException;

final class SkautisGrantId
{
    public function __construct(private ?int $value)
    {
    }

    public function toInt(): int
    {
        if ($this->value === null) {
            throw new LogicException('SkautisGrantId has no value.');
        }

        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
