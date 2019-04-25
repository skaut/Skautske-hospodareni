<?php

declare(strict_types=1);

namespace Model\Travel;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Contract\Passenger as ContractPassenger;
use Model\Unit\Unit;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_contracts")
 */
class Contract
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     *
     * @var int
     */
    private $unitId;

    /**
     * @ORM\Column(type="string", name="unit_person", length=64, options={"comment": "jméno osoby zastupující jednotku"})
     *
     * @var string
     */
    private $unitRepresentative;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true, name="start")
     *
     * @var DateTimeImmutable|NULL
     */
    private $since;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true, name="end")
     *
     * @var DateTimeImmutable|NULL
     */
    private $until;

    /**
     * @ORM\Embedded(class=ContractPassenger::class, columnPrefix=false)
     *
     * @var ContractPassenger
     */
    private $passenger;

    /**
     * @ORM\Column(type="smallint", name="template", options={"comment":"1-old, 2-podle NOZ"})
     *
     * @var int
     */
    private $templateVersion = 2;

    public function __construct(Unit $unit, string $unitRepresentative, DateTimeImmutable $since, ContractPassenger $passenger)
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

    public function getSince() : ?DateTimeImmutable
    {
        return $this->since;
    }

    public function getUntil() : ?DateTimeImmutable
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
