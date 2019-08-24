<?php

declare(strict_types=1);

namespace Model\Travel;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Model\Common\ScanNotFound;
use Model\Travel\Vehicle\Metadata;
use Model\Travel\Vehicle\RoadworthyScan;
use Model\Unit\Unit;
use Nette\SmartObject;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_vehicle")
 *
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
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $unitId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int|NULL
     */
    private $subunitId;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $registration;

    /**
     * @ORM\Column(type="float")
     *
     * @var float
     */
    private $consumption;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $note = '';

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $archived = false;

    /**
     * @ORM\Embedded(class=Metadata::class)
     *
     * @var Metadata
     */
    private $metadata;

    /**
     * @ORM\OneToMany(
     *     targetEntity=RoadworthyScan::class,
     *     mappedBy="vehicle",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     *
     * @var ArrayCollection|RoadworthyScan[]
     */
    private $roadworthyScans;

    public function __construct(string $type, Unit $unit, ?Unit $subunit, string $registration, float $consumption, Metadata $metadata)
    {
        $this->type   = $type;
        $this->unitId = $unit->getId();

        if ($subunit !== null) {
            if (! $subunit->isSubunitOf($unit)) {
                throw new InvalidArgumentException('Unit #{' . $subunit->getId() . '} is not child of #{' . $unit->getId() . '}');
            }

            $this->subunitId = $subunit->getId();
        }

        $this->registration = $registration;
        $this->consumption  = $consumption;
        $this->metadata     = $metadata;

        $this->roadworthyScans = new ArrayCollection();
    }

    public function addRoadworthyScan(string $filePath) : void
    {
        $this->roadworthyScans->add(new RoadworthyScan($this, $filePath));
    }

    public function removeRoadworthyScan(string $filePath) : void
    {
        foreach ($this->roadworthyScans as $key => $scan) {
            if ($scan->getFilePath() === $filePath) {
                $this->roadworthyScans->remove($key);

                return;
            }
        }

        throw ScanNotFound::withPath($filePath);
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

    /**
     * @return RoadworthyScan[]
     */
    public function getRoadworthyScans() : array
    {
        return $this->roadworthyScans->toArray();
    }
}
