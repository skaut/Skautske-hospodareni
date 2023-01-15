<?php

declare(strict_types=1);

namespace Model\Common;

final class UnitId
{
    public function __construct(private int $id)
    {
    }

    public static function fromInt(int $id): self
    {
        return new self($id);
    }

    public function toInt(): int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function equals(self $that): bool
    {
        return $this->toInt() === $that->toInt();
    }
}
