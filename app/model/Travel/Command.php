<?php

declare(strict_types=1);

namespace Model\Travel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Command\TransportTravel;
use Model\Travel\Command\Travel;
use Model\Travel\Command\TravelDetails;
use Model\Travel\Command\VehicleTravel;
use Model\Travel\Travel\TransportType;
use Model\Utils\MoneyFactory;
use Money\Money;
use function array_reduce;
use function array_sum;
use function array_unique;
use function min;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_commands")
 */
class Command
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /** @ORM\Column(type="integer", options={"unsigned"=true}) */
    private int $unitId;

    /** @ORM\ManyToOne(targetEntity=Vehicle::class) */
    private ?Vehicle $vehicle = null;

    /** @ORM\Embedded(class=Passenger::class, columnPrefix=false) */
    private Passenger $passenger;

    /** @ORM\Column(type="string", length=64) */
    private string $purpose;

    /** @ORM\Column(type="string", length=64, nullable=true) */
    private string $place;

    /** @ORM\Column(type="string", name="passengers", length=64) */
    private string $fellowPassengers;

    /** @ORM\Column(type="money") */
    private Money $fuelPrice;

    /** @ORM\Column(type="money") */
    private Money $amortization;

    /** @ORM\Column(type="string", length=64) */
    private string $note;

    /** @ORM\Column(type="datetime_immutable", nullable=true, name="closed") */
    private ?DateTimeImmutable $closedAt = null;

    /**
     * @ORM\OneToMany(targetEntity=Travel::class, indexBy="id", mappedBy="command", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection|Travel[]
     */
    private $travels;

    /** @ORM\Column(type="integer") */
    private int $nextTravelId = 0;

    /** @ORM\Column(type="integer", nullable=true) */
    private ?int $ownerId = null;

    /**
     * @ORM\Column(type="transport_types")
     *
     * @var TransportType[]
     */
    private array $transportTypes;

    /**
     * @var string
     * * @ORM\Column(type="string", length=64)
     */
    private string $unit;

    /**
     * @param TransportType[] $transportTypes
     */
    public function __construct(
        int $unitId,
        ?Vehicle $vehicle,
        Passenger $passenger,
        string $purpose,
        string $place,
        string $fellowPassengers,
        Money $fuelPrice,
        Money $amortization,
        string $note,
        ?int $ownerId,
        array $transportTypes,
        string $unit
    ) {
        $this->unitId           = $unitId;
        $this->vehicle          = $vehicle;
        $this->passenger        = $passenger;
        $this->purpose          = $purpose;
        $this->place            = $place;
        $this->fellowPassengers = $fellowPassengers;
        $this->fuelPrice        = $fuelPrice;
        $this->amortization     = $amortization;
        $this->note             = $note;
        $this->travels          = new ArrayCollection();
        $this->ownerId          = $ownerId;
        $this->transportTypes   = array_unique($transportTypes);
        $this->unit             = $unit;
    }

    /**
     * @param TransportType[] $transportTypes
     */
    public function update(
        ?Vehicle $vehicle,
        Passenger $driver,
        string $purpose,
        string $place,
        string $passengers,
        Money $fuelPrice,
        Money $amortization,
        string $note,
        array $transportTypes,
        string $unit
    ) : void {
        $this->vehicle          = $vehicle;
        $this->passenger        = $driver;
        $this->purpose          = $purpose;
        $this->place            = $place;
        $this->fellowPassengers = $passengers;
        $this->fuelPrice        = $fuelPrice;
        $this->amortization     = $amortization;
        $this->note             = $note;
        $this->transportTypes   = array_unique($transportTypes);
        $this->unit             = $unit;
    }

    public function close(DateTimeImmutable $time) : void
    {
        if ($this->closedAt !== null) {
            return;
        }

        $this->closedAt = $time;
    }

    public function open() : void
    {
        $this->closedAt = null;
    }

    public function addVehicleTravel(float $distance, TravelDetails $details) : void
    {
        $id = $this->getTravelId();
        $this->travels->set($id, new VehicleTravel($id, $distance, $details, $this));
    }

    /**
     * @throws TravelNotFound
     */
    public function updateVehicleTravel(int $id, float $distance, TravelDetails $details) : void
    {
        $travel = $this->getTravel($id);

        if (! $travel instanceof VehicleTravel) {
            $this->removeTravel($id);
            $this->addVehicleTravel($distance, $details);

            return;
        }

        $travel->update($distance, $details);
    }

    public function addTransportTravel(Money $price, TravelDetails $details) : void
    {
        $id = $this->getTravelId();
        $this->travels->set($id, new TransportTravel($id, $price, $details, $this));
    }

    /**
     * @throws TravelNotFound
     */
    public function updateTransportTravel(int $id, Money $price, TravelDetails $details) : void
    {
        $travel = $this->getTravel($id);
        if (! $travel instanceof TransportTravel) {
            $this->removeTravel($id);
            $this->addTransportTravel($price, $details);

            return;
        }

        $travel->update($price, $details);
    }

    public function removeTravel(int $id) : void
    {
        $this->travels->remove($id);
    }

    public function calculateTotal() : Money
    {
        $amount = $this->getTransportPrice()->add($this->getVehiclePrice());

        return MoneyFactory::floor($amount);
    }

    public function getPriceFor(VehicleTravel $travel) : Money
    {
        return MoneyFactory::fromFloat($travel->getDistance() * $this->getPricePerKmMultiplier());
    }

    private function getDistance() : float
    {
        $distances = $this->travels
                    ->filter(function (Travel $t) {
                        return $t instanceof VehicleTravel;
                    })
                    ->map(function (VehicleTravel $t) {
                        return $t->getDistance();
                    })
                    ->toArray();

        return array_sum($distances);
    }

    private function getTransportPrice() : Money
    {
        $prices = $this->travels->filter(function (Travel $t) {
            return $t instanceof TransportTravel;
        })->toArray();

        return array_reduce(
            $prices,
            function (Money $total, TransportTravel $travel) {
                return $total->add($travel->getPrice());
            },
            MoneyFactory::zero()
        );
    }

    /**
     * Rounded price per km - do not use for calculation
     */
    public function getPricePerKm() : Money
    {
        $distance = $this->getDistance();

        return $distance !== 0.0
            ? $this->getVehiclePrice()->divide($this->getDistance())
            : MoneyFactory::zero();
    }

    private function getVehiclePrice() : Money
    {
        if ($this->vehicle === null) {
            return MoneyFactory::zero();
        }

        $distance = $this->getDistance();

        $fuelPrice = $this->fuelPrice->multiply($distance * $this->vehicle->getConsumption() / 100);

        return $this->amortization
                    ->multiply($distance)
                    ->add($fuelPrice);
    }

    private function getPricePerKmMultiplier() : float
    {
        return $this->vehicle === null
            ? 0
            : MoneyFactory::toFloat($this->amortization) + MoneyFactory::toFloat($this->fuelPrice) * $this->vehicle->getConsumption() / 100;
    }

    public function getFuelPricePerKm() : Money
    {
        if ($this->vehicle === null) {
            return MoneyFactory::zero();
        }

        return $this->fuelPrice->multiply($this->vehicle->getConsumption() / 100);
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getVehicleId() : ?int
    {
        return $this->vehicle !== null
            ? $this->vehicle->getId()
            : null;
    }

    public function getPassenger() : Passenger
    {
        return $this->passenger;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function getPlace() : string
    {
        return $this->place;
    }

    public function getFellowPassengers() : string
    {
        return $this->fellowPassengers;
    }

    public function getFuelPrice() : Money
    {
        return $this->fuelPrice;
    }

    public function getAmortization() : Money
    {
        return $this->amortization;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function getClosedAt() : ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    /**
     * Only for reading
     *
     * @internal
     *
     * @return Travel[]
     */
    public function getTravels() : array
    {
        return $this->travels->getValues();
    }

    public function getFirstTravelDate() : ?DateTimeImmutable
    {
        return $this->travels->isEmpty()
            ? null
            : min($this->travels->map(function (Travel $travel) {
                return $travel->getDetails()->getDate();
            })->toArray());
    }

    public function getTravelCount() : int
    {
        return $this->travels->count();
    }

    /**
     * @throws TravelNotFound
     */
    private function getTravel(int $id) : Travel
    {
        $travel = $this->travels->get($id);

        if ($travel === null) {
            throw new TravelNotFound('Travel #' . $id . ' not found');
        }

        return $travel;
    }

    /**
     * Returns all transport types that have at least one travel
     *
     * @return TransportType[]
     */
    public function getUsedTransportTypes() : array
    {
        return array_unique(
            $this->travels->map(fn(Travel $travel) => $travel->getDetails()->getTransportType())->toArray()
        );
    }

    private function getTravelId() : int
    {
        return $this->nextTravelId++;
    }

    public function getOwnerId() : ?int
    {
        return $this->ownerId;
    }

    /**
     * @return TransportType[]
     */
    public function getTransportTypes() : array
    {
        return $this->transportTypes;
    }

    public function getUnit() : string
    {
        return $this->unit;
    }
}
