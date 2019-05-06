<?php

declare(strict_types=1);

namespace Model\Event;

final class SkautisCampId
{
    /** @var int */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @deprecated use self::toInt()
     */
    public function getValue() : int
    {
        return $this->value;
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
