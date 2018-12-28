<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

class Type
{
    /** @var string */
    private $type;

    /** @var string */
    private $label;

    /** @var bool */
    private $hasFuel;

    public function __construct(string $type, string $label, bool $hasFuel)
    {
        $this->type    = $type;
        $this->label   = $label;
        $this->hasFuel = $hasFuel;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function hasFuel() : bool
    {
        return $this->hasFuel;
    }
}
