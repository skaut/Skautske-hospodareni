<?php

namespace Model\DTO\Travel;

use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $label
 * @property-read bool $archived
 */
class Vehicle
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $label;

    /** @var bool */
    private $archived;


    public function __construct(int $id, string $label, bool $archived)
    {
        $this->id = $id;
        $this->label = $label;
        $this->archived = $archived;
    }


    public function getId(): int
    {
        return $this->id;
    }


    public function getLabel(): string
    {
        return $this->label;
    }


    public function isArchived(): bool
    {
        return $this->archived;
    }

}
