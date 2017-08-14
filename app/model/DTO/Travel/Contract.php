<?php


namespace Model\DTO\Travel;

use Model\Travel\Contract\Passenger;
use Nette\SmartObject;


/**
 * @property-read int                       $id
 * @property-read Passenger                 $passenger
 * @property-read int                       $unitId
 * @property-read string                    $unitRepresentative
 * @property-read \DateTimeImmutable|NULL   $since
 * @property-read \DateTimeImmutable|NULL   $until
 * @property-read int                       $templateVersion
 */
class Contract
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var Passenger */
    private $passenger;

    /** @var int */
    private $unitId;

    /** @var string */
    private $unitRepresentative;

    /** @var \DateTimeImmutable|NULL */
    private $since;

    /** @var \DateTimeImmutable|NULL */
    private $until;

    /** @var int */
    private $templateVersion;

    public function __construct(
        int $id,
        Passenger $passenger,
        int $unitId,
        string $unitRepresentative,
        ?\DateTimeImmutable $since,
        ?\DateTimeImmutable $until,
        int $templateVersion
    )
    {
        $this->id = $id;
        $this->passenger = $passenger;
        $this->unitId = $unitId;
        $this->unitRepresentative = $unitRepresentative;
        $this->since = $since;
        $this->until = $until;
        $this->templateVersion = $templateVersion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPassenger(): Passenger
    {
        return $this->passenger;
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

    public function getTemplateVersion(): int
    {
        return $this->templateVersion;
    }

}
