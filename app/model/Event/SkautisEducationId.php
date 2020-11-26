<?php

declare(strict_types=1);

namespace Model\Event;

final class SkautisEducationId
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function toInt() : int
    {
        return $this->value;
    }

    public function __toString() : string
    {
        return (string) $this->value;
    }
}
