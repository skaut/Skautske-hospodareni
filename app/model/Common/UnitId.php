<?php

declare(strict_types=1);

namespace Model\Common;

final class UnitId
{
    /** @var int */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function fromInt(int $id) : self
    {
        return new self($id);
    }

    public function toInt() : int
    {
        return $this->id;
    }

    public function __toString() : string
    {
        return (string) $this->id;
    }

    public function equals(self $that) : bool
    {
        return $this->toInt() === $that->toInt();
    }
}
