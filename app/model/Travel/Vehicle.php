<?php

namespace Model\Travel;

use Model\Unit\Unit;
use Nette;

class Vehicle extends Nette\Object
{

    /** @var int */
    private $id;

    /** @var string */
    private $type;

    /** @var int */
    private $unitId;

    /** @var int|NULL */
    private $subunitId;

    /** @var string */
    private $registration;

    /** @var float */
    private $consumption;

    /** @var string|NULL */
    private $note = '';

    /** @var bool */
    private $archived = FALSE;


    public function __construct(string $type, Unit $unit, ?Unit $subunit, string $registration, float $consumption)
    {
        $this->type = $type;
        $this->unitId = $unit->getId();

        if($subunit !== NULL) {
            if( ! $subunit->isSubunitOf($unit)) {
                throw new \InvalidArgumentException("Unit #{$subunit->getId()} is not child of #{$unit->getId()}");
            }

            $this->subunitId = $subunit->getId();
        }

        $this->registration = $registration;
        $this->consumption = $consumption;
    }

    public function archive(): void
    {
        $this->archived = TRUE;
    }


    public function getType(): string
    {
        return $this->type;
    }


    public function getId(): int
    {
        return $this->id;
    }


    public function getSubunitId(): ?int
    {
        return $this->subunitId;
    }


    public function getUnitId(): int
    {
        return $this->unitId;
    }


    public function getRegistration(): string
    {
        return $this->registration;
    }


    public function getConsumption(): float
    {
        return $this->consumption;
    }


    public function getNote(): ?string
    {
        return $this->note;
    }


    public function isArchived(): bool
    {
        return $this->archived;
    }


    public function getLabel(): string
    {
        return $this->type . ' (' . $this->registration . ')';
    }

}
