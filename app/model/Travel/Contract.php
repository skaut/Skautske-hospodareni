<?php

declare(strict_types=1);

namespace Model\Travel;

use Model\Travel\Contract\Passenger as ContractPassenger;
use Model\Unit\Unit;

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

    /** @var ContractPassenger */
    private $passenger;

    /** @var int */
    private $templateVersion = 2;


    public function __construct(Unit $unit, string $unitRepresentative, \DateTimeImmutable $since, ContractPassenger $passenger)
    {
        $this->unitId             = $unit->getId();
        $this->unitRepresentative = $unitRepresentative;
        $this->since              = $since->setTime(0, 0, 0);
        $this->until              = $this->since->modify('+ 3 years');
        $this->passenger          = $passenger;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getUnitRepresentative() : string
    {
        return $this->unitRepresentative;
    }

    public function getSince() : ?\DateTimeImmutable
    {
        return $this->since;
    }

    public function getUntil() : ?\DateTimeImmutable
    {
        return $this->until;
    }

    public function getPassenger() : ContractPassenger
    {
        return $this->passenger;
    }

    public function getTemplateVersion() : int
    {
        return $this->templateVersion;
    }
}
