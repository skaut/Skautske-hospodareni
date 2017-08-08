<?php

namespace Model\Unit;

class Unit
{

    /** @var int */
    private $id;

    /** @var string */
    private $sortName;

    /** @var string */
    private $displayName;


    public function __construct(int $id, string $sortName, string $displayName)
    {
        $this->id = $id;
        $this->sortName = $sortName;
        $this->displayName = $displayName;
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
