<?php

namespace Model\Travel;

class Contract
{

    /** @var int */
    private $id;

    /** @var string */
    private $driverName;

    /** @var string */
    private $driverContact;

    /** @var string */
    private $driverAddress;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function getDriverContact(): string
    {
        return $this->driverContact;
    }

    public function getDriverAddress(): string
    {
        return $this->driverAddress;
    }

}
