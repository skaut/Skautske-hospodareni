<?php

namespace Model\Travel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Model\Travel\Command\TransportTravel;
use Model\Travel\Command\TransportType;
use Model\Travel\Command\Travel;
use Model\Travel\Command\TravelDetails;
use Model\Travel\Command\VehicleTravel;
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

    /** @var DateTimeImmutable|NULL */
    private $closedAt;

    /** @var ArrayCollection|TransportTravel[] */
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
        DateTimeImmutable $date,
        float $distanceOrAmount,
        TransportType $type,
        string $startPlace,
        string $endPlace): void
    {
        $details = new TravelDetails($date, $type->getShortcut(), $startPlace, $endPlace, $type->getShortcut());

        if($type->hasFuel()) {
            $this->addVehicleTravel($distanceOrAmount, $details);
            return;
        }
        $this->addTransportTravel(MoneyFactory::fromFloat($distanceOrAmount), $details);
    }

    public function addVehicleTravel(float $distance, TravelDetails $details): void
    {
        $this->travels->add(new VehicleTravel($distance, $details, $this));
    }

    public function addTransportTravel(Money $price, TravelDetails $details): void
    {
        $this->travels->add(new TransportTravel($price, $details, $this));
    }

    public function calculateTotal(): Money
    {
        return $this->getTransportPrice()
                    ->add($this->getVehiclePrice());
    }

    private function getDistance(): float
    {
        $distances = $this->travels
                    ->filter(function(Travel $t) { return $t instanceof VehicleTravel; })
                    ->map(function(VehicleTravel $t) { return $t->getDistance(); })
                    ->toArray();

        return array_sum($distances);
    }

    private function getTransportPrice(): Money
    {
        $prices = $this->travels->filter(function(Travel $t) { return $t instanceof TransportTravel; })->toArray();

        return array_reduce(
            $prices,
            function(Money $total, TransportTravel $travel) { return $total->add($travel->getPrice()); },
            Money::CZK(0)
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

        $distance = $this->getDistance();
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

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getFirstTravelDate(): ?DateTimeImmutable
    {
        return $this->travels->isEmpty()
            ? NULL
            : min($this->travels->map(function(Travel $travel) { return $travel->getDetails()->getDate(); })->toArray());
    }

}
