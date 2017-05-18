<?php

namespace Model\Travel;

use Doctrine\Common\Collections\ArrayCollection;
use Model\Travel\Command\TransportType;
use Model\Travel\Command\Travel;
use Model\Utils\MoneyFactory;
use Money\Money;

class Command
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var Vehicle|NULL */
    private $vehicle;

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
    private $amortization;

    /** @var string */
    private $note;

    /** @var \DateTimeImmutable|NULL */
    private $closedAt;

    /** @var ArrayCollection|Travel[] */
    private $travels;

    public function __construct(
        int $unitId, ?Vehicle $vehicle, Passenger $passenger, string $purpose,
        string $place, string $fellowPassengers, Money $fuelPrice, Money $amortization, string $note
    )
    {
        $this->unitId = $unitId;
        $this->vehicle = $vehicle;
        $this->passenger = $passenger;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->fellowPassengers = $fellowPassengers;
        $this->fuelPrice = $fuelPrice;
        $this->amortization = $amortization;
        $this->note = $note;
        $this->travels = new ArrayCollection();
    }

    public function update(
        ?Vehicle $vehicle,
        Passenger $driver,
        string $purpose,
        string $place,
        string $passengers,
        Money $fuelPrice,
        Money $amortization,
        string $note
    ): void
    {
        $this->vehicle = $vehicle;
        $this->passenger = $driver;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->fellowPassengers = $passengers;
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

    public function calculateTotal(): Money
    {
        $amount = array_sum(
            $this->travels->map(function(Travel $travel) {
                return !$travel->getTransportType()->hasFuel() ? $travel->getAmount() : 0;
            })->toArray()
        );

        $amount = MoneyFactory::fromFloat($amount);
        $amount = $amount->add($this->getVehiclePrice());

        return $amount;
    }

    private function getDistance(): float
    {
        return array_sum(
            $this->travels->map(function(Travel $travel) {
                return $travel->getTransportType()->hasFuel() ? $travel->getDistance() : 0;
            })->toArray()
        );
    }

    /**
     * Rounded price per km - do not use for calculation
     * @return Money
     */
    public function getPricePerKm(): Money
    {
        $distance = $this->getDistance();
        return $distance !== 0.0 ? $this->getVehiclePrice()->divide($this->getDistance()) : Money::CZK(0);
    }

    private function getVehiclePrice(): Money
    {
        if($this->vehicle === NULL) {
            return Money::CZK(0);
        }

        $distance = array_sum(
            $this->travels->map(function (Travel $travel) {
                return $travel->getTransportType()->hasFuel() ? $travel->getDistance() : 0;
            })->toArray()
        );

        $fuelPrice = $this->fuelPrice->multiply($distance * $this->vehicle->getConsumption() / 100);

        return $this->amortization->multiply($distance)->add($fuelPrice);
    }

    public function getFuelPricePerKm(): Money
    {
        if($this->vehicle === NULL) {
            return Money::CZK(0);
        }

        return $this->fuelPrice->multiply($this->vehicle->getConsumption() / 100);
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

    public function getAmortization(): Money
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
