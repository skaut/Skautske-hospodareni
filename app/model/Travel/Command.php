<?php

namespace Model\Travel;

use Doctrine\Common\Collections\ArrayCollection;
use Model\Travel\Command\Travel;

class Command
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var Vehicle|NULL */
    private $vehicle;

    /** @var Driver */
    private $driver;

    /** @var string */
    private $purpose;

    /** @var string */
    private $place;

    /** @var string */
    private $passengers;

    /** @var float */
    private $fuelPrice;

    /** @var float */
    private $amortization;

    /** @var string */
    private $note;

    /** @var \DateTimeImmutable|NULL */
    private $closedAt;

    /** @var ArrayCollection|Travel[] */
    private $travels;

    public function __construct(
        int $unitId, ?Vehicle $vehicle, Driver $driver, string $purpose,
        string $place, string $passengers, float $fuelPrice, float $amortization, string $note
    )
    {
        $this->unitId = $unitId;
        $this->vehicle = $vehicle;
        $this->driver = $driver;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->passengers = $passengers;
        $this->fuelPrice = $fuelPrice;
        $this->amortization = $amortization;
        $this->note = $note;
        $this->travels = new ArrayCollection();
    }

    public function update(
        ?Vehicle $vehicle,
        Driver $driver,
        string $purpose,
        string $place,
        string $passengers,
        float $fuelPrice,
        float $amortization,
        string $note
    ): void
    {
        $this->vehicle = $vehicle;
        $this->driver = $driver;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->passengers = $passengers;
        $this->fuelPrice = $fuelPrice;
        $this->amortization = $amortization;
        $this->note = $note;
    }

    public function createTravel(
        \DateTimeImmutable $date,
        float $distanceOrAmount,
        TransportType $type,
        string $startPlace,
        string $endPlace): void
    {
        $this->travels->add(
            new Travel($date, $distanceOrAmount, $type, $startPlace, $endPlace, $this)
        );
    }

    public function calculateTotal(): float
    {
        $amount = array_sum(
            $this->travels->map(function(Travel $travel) {
                return !$travel->getTransportType()->hasFuel() ? $travel->getAmount() : 0;
            })->toArray()
        );

        return $amount + $this->calculateFuelPrice();
    }

    private function getDistance(): float
    {
        return array_sum(
            $this->travels->map(function(Travel $travel) {
                return $travel->getTransportType()->hasFuel() ? $travel->getDistance() : 0;
            })->toArray()
        );
    }

    public function calculateAmortization(): float
    {
        return $this->getDistance() * $this->amortization;
    }

    private function calculateFuelPrice(): float
    {
        return $this->vehicle === NULL
            ? 0
            : $this->getDistance() / 100 * $this->vehicle->getConsumption() * $this->fuelPrice;
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
        return $this->vehicle !== NULL
            ? $this->vehicle->getId()
            : NULL;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function getPassengers(): string
    {
        return $this->passengers;
    }

    public function getFuelPrice(): float
    {
        return $this->fuelPrice;
    }

    public function getAmortization(): float
    {
        return $this->amortization;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * @return Travel[]
     */
    public function getTravels(): array
    {
        return $this->travels->toArray();
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getFirstTravelDate(): ?\DateTimeImmutable
    {
        if($this->travels->isEmpty()) {
            return NULL;
        }

        return min(
            $this->travels->map(function(Travel $travel) {
                return $travel->getDate();
            })->toArray()
        );
    }


}
