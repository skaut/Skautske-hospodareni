<?php

declare(strict_types=1);

namespace Model\Travel;

use Cake\Chronos\ChronosDate;
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
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="integer") */
    private int $unitId;

    /** @ORM\Column(type="string", length=64) */
    private string $unitRepresentative;

    /** @ORM\Column(type="chronos_date", nullable=true) */
    private ChronosDate|null $since = null;

    /** @ORM\Column(type="chronos_date", nullable=true) */
    private ChronosDate|null $until = null;

    /** @ORM\Embedded(class=ContractPassenger::class, columnPrefix=false) */
    private ContractPassenger $passenger;

    /** @ORM\Column(type="smallint", options={"comment":"1-old, 2-podle NOZ"}) */
    private int $templateVersion = 2;

    public function __construct(Unit $unit, string $unitRepresentative, ChronosDate $since, ContractPassenger $passenger)
    {
        $this->unitId             = $unit->getId();
        $this->unitRepresentative = $unitRepresentative;
        $this->since              = $since;
        $this->until              = $this->since->modify('+ 3 years');
        $this->passenger          = $passenger;
    }

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

    public function getSince(): ChronosDate|null
    {
        return $this->since;
    }

    public function getUntil(): ChronosDate|null
    {
        return $this->until;
    }

    public function getPassenger(): ContractPassenger
    {
        return $this->passenger;
    }

    public function getTemplateVersion(): int
    {
        return $this->templateVersion;
    }
}
