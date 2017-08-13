<?php

namespace Model\Unit;

use Nette\Utils\Strings;

class Unit
{

    /** @var int */
    private $id;

    /** @var string */
    private $sortName;

    /** @var string */
    private $displayName;

    /** @var string */
    private $registrationNumber;


    public function __construct(int $id, string $sortName, string $displayName, string $registrationNumber)
    {
        $this->id = $id;
        $this->sortName = $sortName;
        $this->displayName = $displayName;
        $this->registrationNumber = $registrationNumber;
    }


    public function isSubunitOf(Unit $unit): bool
    {
        return Strings::startsWith($this->registrationNumber, $unit->registrationNumber);
    }


    public function getId(): int
    {
        return $this->id;
    }


    public function getSortName(): string
    {
        return $this->sortName;
    }


    public function getDisplayName(): string
    {
        return $this->displayName;
    }

}
