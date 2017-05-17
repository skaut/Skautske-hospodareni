<?php

namespace Model\DTO\Travel;

use Model\Travel\Passenger;
use Nette\SmartObject;

/**
 * @property-read int                       $id
 * @property-read int                       $unitId
 * @property-read int|NULL                  $vehicleId
 * @property-read Passenger                 $passenger
 * @property-read string                    $purpose
 * @property-read string                    $place
 * @property-read string                    $fellowPassengers
 * @property-read float                     $fuelPrice
 * @property-read float                     $amortizationPerKm
 * @property-read string                    $note
 * @property-read \DateTimeImmutable|NULL   $closedAt
 * @property-read float                     $total
 * @property-read \DateTimeImmutable|NULL   $firstTravelDate
 */
class Command
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var int|NULL */
    private $vehicleId;

    /** @var Passenger */
    private $passenger;

    /** @var string */
    private $purpose;

    /** @var string */
    private $place;

    /** @var string */
    private $fellowPassengers;

    /** @var float */
    private $fuelPrice;

    /** @var float */
    private $amortizationPerKm;

    /** @var string */
    private $note;

    /** @var float */
    private $total;

    /** @var \DateTimeImmutable|NULL */
    private $closedAt;

    /** @var \DateTimeImmutable|NULL */
    private $firstTravelDate;

    public function __construct(
        int $id,
        int $unitId,
        ?int $vehicleId,
        Passenger $passenger,
        string $purpose,
        string $place,
        string $fellowPassengers,
        float $fuelPrice,
        float $amortizationPerKm,
        string $note,
        ?\DateTimeImmutable $closedAt,
        float $total,
        ?\DateTimeImmutable $firstTravelDate
    )
    {
        $this->id = $id;
        $this->unitId = $unitId;
        $this->vehicleId = $vehicleId;
        $this->passenger = $passenger;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->fellowPassengers = $fellowPassengers;
        $this->fuelPrice = $fuelPrice;
        $this->amortizationPerKm = $amortizationPerKm;
        $this->note = $note;
        $this->total = $total;
        $this->closedAt = $closedAt;
        $this->firstTravelDate = $firstTravelDate;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getVehicleId(): ?int
    {
        return $this->vehicleId;
    }

    public function getPassenger(): Passenger
    {
        return $this->passenger;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function getFellowPassengers(): string
    {
        return $this->fellowPassengers;
    }

    public function getFuelPrice(): float
    {
        return $this->fuelPrice;
    }

    public function getAmortizationPerKm(): float
    {
        return $this->amortizationPerKm;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getFirstTravelDate(): ?\DateTimeImmutable
    {
        return $this->firstTravelDate;
    }

}
