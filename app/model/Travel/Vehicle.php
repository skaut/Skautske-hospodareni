<?php

declare(strict_types=1);

namespace Model\Travel;

use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Vehicle\Metadata;
use Model\Unit\Unit;
use Nette\SmartObject;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_vehicle")
 * @property-read int           $id
 * @property-read string        $type
 * @property-read int           $unitId
 * @property-read int|NULL      $subunitId
 * @property-read string        $registration
 * @property-read float         $consumption
 * @property-read string        $note
 * @property-read bool          $archived
 * @property-read string        $label
 * @property-read Metadata      $metadata
 */
class Vehicle
{
    use SmartObject;

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $unitId;

    /**
     * @var int|NULL
     * @ORM\Column(type="integer", nullable=true)
     */
    private $subunitId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $registration;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $consumption;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $note = '';

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $archived = false;

    /**
     * @var Metadata
     * @ORM\Embedded(class=Metadata::class)
     */
    private $metadata;

    public function __construct(string $type, Unit $unit, ?Unit $subunit, string $registration, float $consumption, Metadata $metadata)
    {
        $this->type   = $type;
        $this->unitId = $unit->getId();

        if ($subunit !== null) {
            if (! $subunit->isSubunitOf($unit)) {
                throw new \InvalidArgumentException('Unit #{' . $subunit->getId() . '} is not child of #{' . $unit->getId() . '}');
            }

            $this->subunitId = $subunit->getId();
        }

        $this->registration = $registration;
        $this->consumption  = $consumption;
        $this->metadata     = $metadata;
    }

    public function archive() : void
    {
        $this->archived = true;
    }


    public function getType() : string
    {
        return $this->type;
    }


    public function getId() : int
    {
        return $this->id;
    }


    public function getSubunitId() : ?int
    {
        return $this->subunitId;
    }


    public function getUnitId() : int
    {
        return $this->unitId;
    }


    public function getRegistration() : string
    {
        return $this->registration;
    }


    public function getConsumption() : float
    {
        return $this->consumption;
    }


    public function getNote() : string
    {
        return $this->note;
    }


    public function isArchived() : bool
    {
        return $this->archived;
    }


    public function getLabel() : string
    {
        return $this->type . ' (' . $this->registration . ')';
    }

    public function getMetadata() : Metadata
    {
        return $this->metadata;
    }
}
