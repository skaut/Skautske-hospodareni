<?php

declare(strict_types=1);

namespace Model\Event;

final class SkautisEventId
{
    public function __construct(private int $value)
    {
    }

    public function toInt(): int
    {
        return $this->value;
    }

    /** @deprecated use self::toInt() */
    public function getValue(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
