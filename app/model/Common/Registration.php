<?php

declare(strict_types=1);

namespace Model\Common;

final class Registration
{
    /** @var int */
    private $id;

    /** @var string */
    private $unitName;

    /** @var int */
    private $year;

    public function __construct(int $id, string $unitName, int $year)
    {
        $this->id       = $id;
        $this->unitName = $unitName;
        $this->year     = $year;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitName() : string
    {
        return $this->unitName;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}
