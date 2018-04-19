<?php

namespace Model\Travel;

use Model\Travel\Vehicle\Metadata;
use Model\Unit\Unit;
use Nette\SmartObject;

/**
 * @property-read int           $id
 * @property-read string        $type
 * @property-read int           $unitId
 * @property-read int|NULL      $subunitId
 * @property-read string        $registration
 * @property-read float         $consumption
 * @property-read string|NULL   $note
 * @property-read bool          $archived
 * @property-read Metadata      $metadata
 */
class Vehicle
{

    use SmartObject;

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

    /** @var Metadata */
    private $metadata;


    public function __construct(string $type, Unit $unit, ?Unit $subunit, string $registration, float $consumption, Metadata $metadata)
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
        $this->metadata = $metadata;
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

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

}
