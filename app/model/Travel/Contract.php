<?php

namespace Model\Travel;

class Contract
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var string */
    private $unitRepresentative;

    /** @var \DateTimeImmutable|NULL */
    private $since;

    /** @var \DateTimeImmutable|NULL */
    private $until;

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

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUnitRepresentative(): string
    {
        return $this->unitRepresentative;
    }

    public function getSince(): ?\DateTimeImmutable
    {
        return $this->since;
    }

    public function getUntil(): ?\DateTimeImmutable
    {
        return $this->until;
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
