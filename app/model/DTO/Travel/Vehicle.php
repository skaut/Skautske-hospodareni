<?php

namespace Model\DTO\Travel;

use Nette\SmartObject;

class Vehicle
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $label;

    public function __construct(int $id, string $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

}
