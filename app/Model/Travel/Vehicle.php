<?php

declare(strict_types=1);

namespace App\Model\Travel;

use App\Model\Common\FilePath;
use App\Model\Common\ScanNotFound;
use App\Model\Travel\Vehicle\Metadata;
use App\Model\Travel\Vehicle\RoadworthyScan;
use App\Model\Unit\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * @property int      $id
 * @property string   $type
 * @property int      $unitId
 * @property int|null $subunitId
 * @property string   $registration
 * @property float    $consumption
 * @property string   $note
 * @property bool     $archived
 * @property string   $label
 * @property Metadata $metadata
 */
#[ORM\Entity]
#[ORM\Table(name: 'tc_vehicle')]
class Vehicle
{
    use SmartObject;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 64)]
    private string $type;

    #[ORM\Column(type: 'integer')]
    private int $unitId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $subunitId = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $registration;

    #[ORM\Column(type: 'float')]
    private float $consumption;

    #[ORM\Column(type: 'string', length: 64)]
    private string $note = '';

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $archived = false;

    #[ORM\Embedded(class: Metadata::class)]
    private Metadata $metadata;

    /**
     * @var Collection&iterable<RoadworthyScan>
     */
    #[ORM\OneToMany(targetEntity: RoadworthyScan::class, mappedBy: 'vehicle', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $roadworthyScans;

    public function __construct(string $type, Unit $unit, ?Unit $subunit, string $registration, float $consumption, Metadata $metadata)
    {
        $this->type = $type;
        $this->unitId = $unit->getId();

        if ($subunit !== null) {
            $this->subunitId = $subunit->getId();
        }

        $this->registration = $registration;
        $this->consumption = $consumption;
        $this->metadata = $metadata;

        $this->roadworthyScans = new ArrayCollection();
    }

    public function addRoadworthyScan(FilePath $filePath): void
    {
        $this->roadworthyScans->add(new RoadworthyScan($this, $filePath));
    }

    public function removeRoadworthyScan(FilePath $filePath): void
    {
        foreach ($this->roadworthyScans as $key => $scan) {
            if ($scan->getFilePath()->equals($filePath)) {
                $this->roadworthyScans->remove($key);

                return;
            }
        }

        throw ScanNotFound::withPath($filePath);
    }

    public function archive(): void
    {
        $this->archived = true;
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

    public function getNote(): string
    {
        return $this->note;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function getLabel(): string
    {
        return $this->type.' ('.$this->registration.')';
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /** @return RoadworthyScan[] */
    public function getRoadworthyScans(): array
    {
        return $this->roadworthyScans->toArray();
    }
}
