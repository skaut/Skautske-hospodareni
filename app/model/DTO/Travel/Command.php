<?php

namespace Model\DTO\Travel;

use Model\Travel\Passenger;
use Money\Money;
use Nette\SmartObject;

/**
 * @property-read int                       $id
 * @property-read int                       $unitId
 * @property-read int|NULL                  $vehicleId
 * @property-read Passenger                 $passenger
 * @property-read string                    $purpose
 * @property-read string                    $place
 * @property-read string                    $fellowPassengers
 * @property-read Money                     $fuelPrice
 * @property-read Money                     $amortizationPerKm
 * @property-read string                    $note
 * @property-read \DateTimeImmutable|NULL   $closedAt
 * @property-read Money                     $total
 * @property-read \DateTimeImmutable|NULL   $firstTravelDate
 * @property-read Money                     $pricePerKm
 * @property-read Money                     $fuelPricePerKm
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

    /** @var Money */
    private $fuelPrice;

    /** @var Money */
    private $amortizationPerKm;

    /** @var string */
    private $note;

    /** @var Money */
    private $total;

    /** @var \DateTimeImmutable|NULL */
    private $closedAt;

    /** @var \DateTimeImmutable|NULL */
    private $firstTravelDate;

    /** @var Money */
    private $pricePerKm;

    /** @var Money */
    private $fuelPricePerKm;

    public function __construct(
        int $id,
        int $unitId,
        ?int $vehicleId,
        Passenger $passenger,
        string $purpose,
        string $place,
        string $fellowPassengers,
        Money $fuelPrice,
        Money $amortizationPerKm,
        string $note,
        ?\DateTimeImmutable $closedAt,
        Money $total,
        ?\DateTimeImmutable $firstTravelDate,
        Money $pricePerKm,
        Money $fuelPricePerKm
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
        $this->pricePerKm = $pricePerKm;
        $this->fuelPricePerKm = $fuelPricePerKm;
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

    public function getFuelPrice(): Money
    {
        return $this->fuelPrice;
    }

    public function getAmortizationPerKm(): Money
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

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getFirstTravelDate(): ?\DateTimeImmutable
    {
        return $this->firstTravelDate;
    }

    public function getPricePerKm(): Money
    {
        return $this->pricePerKm;
    }

    public function getFuelPricePerKm(): Money
    {
        return $this->fuelPricePerKm;
    }

}
