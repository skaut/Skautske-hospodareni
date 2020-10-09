<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use DateTimeImmutable;
use Model\Travel\Passenger;
use Model\Travel\Travel\TransportType;
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
 * @property-read DateTimeImmutable|NULL $closedAt
 * @property-read Money                     $total
 * @property-read DateTimeImmutable|NULL $firstTravelDate
 * @property-read Money                     $pricePerKm
 * @property-read Money                     $fuelPricePerKm
 * @property-read string                    $state
 * @property-read TransportType[]           $transportTypes
 * @property-read string[]                  $transportTypePairs
 * @property-read string                    $unit
 */
class Command
{
    use SmartObject;

    public const STATE_CLOSED      = 'closed';
    public const STATE_IN_PROGRESS = 'in_progress';

    private int $id;

    private int $unitId;

    private ?int $vehicleId;

    private Passenger $passenger;

    private string $purpose;

    private string $place;

    private string $fellowPassengers;

    private Money $fuelPrice;

    private Money $amortizationPerKm;

    private string $note;

    private Money $total;

    private ?DateTimeImmutable $closedAt;

    private ?DateTimeImmutable $firstTravelDate;

    private Money $pricePerKm;

    private Money $fuelPricePerKm;

    private string $state;

    private ?int $ownerId;

    /** @var TransportType[] */
    private array $transportTypes;

    private string $unit;

    /**
     * @param TransportType[] $transportTypes
     */
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
        ?DateTimeImmutable $closedAt,
        Money $total,
        ?DateTimeImmutable $firstTravelDate,
        Money $pricePerKm,
        Money $fuelPricePerKm,
        string $state,
        ?int $ownerId,
        array $transportTypes,
        string $unit
    ) {
        $this->id                = $id;
        $this->unitId            = $unitId;
        $this->vehicleId         = $vehicleId;
        $this->passenger         = $passenger;
        $this->purpose           = $purpose;
        $this->place             = $place;
        $this->fellowPassengers  = $fellowPassengers;
        $this->fuelPrice         = $fuelPrice;
        $this->amortizationPerKm = $amortizationPerKm;
        $this->note              = $note;
        $this->total             = $total;
        $this->closedAt          = $closedAt;
        $this->firstTravelDate   = $firstTravelDate;
        $this->pricePerKm        = $pricePerKm;
        $this->fuelPricePerKm    = $fuelPricePerKm;
        $this->state             = $state;
        $this->ownerId           = $ownerId;
        $this->transportTypes    = $transportTypes;
        $this->unit              = $unit;
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
        return $this->vehicleId;
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

    public function getAmortizationPerKm() : Money
    {
        return $this->amortizationPerKm;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function getClosedAt() : ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getTotal() : Money
    {
        return $this->total;
    }

    public function getFirstTravelDate() : ?DateTimeImmutable
    {
        return $this->firstTravelDate;
    }

    public function getPricePerKm() : Money
    {
        return $this->pricePerKm;
    }

    public function getFuelPricePerKm() : Money
    {
        return $this->fuelPricePerKm;
    }

    public function getState() : string
    {
        return $this->state;
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

    /**
     * @return string[]
     */
    public function getTransportTypePairs() : array
    {
        $types = [];

        foreach ($this->transportTypes as $type) {
            $types[$type->toString()] = $type->getLabel();
        }

        return $types;
    }

    public function getUnit() : string
    {
        return $this->unit;
    }
}
