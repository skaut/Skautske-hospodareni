<?php

namespace Model\Event;

class Event
{

    /** @var int */
    private $id;

    /** @var string */
    private $displayName;

    /** @var int */
    private $unitId;

    /** @var string */
    private $state;


    public function __construct(int $id, string $displayName, int $unitId, string $state)
    {
        $this->id = $id;
        $this->displayName = $displayName;
        $this->unitId = $unitId;
        $this->state = $state;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function isOpen(): string
    {
        return $this->state === "draft";
    }


}
