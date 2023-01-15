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

    /** @param TransportType[] $transportTypes */
    public function __construct(
        private int $id,
        private int $unitId,
        private int|null $vehicleId = null,
        private Passenger $passenger,
        private string $purpose,
        private string $place,
        private string $fellowPassengers,
        private Money $fuelPrice,
        private Money $amortizationPerKm,
        private string $note,
        private DateTimeImmutable|null $closedAt = null,
        private Money $total,
        private DateTimeImmutable|null $firstTravelDate = null,
        private Money $pricePerKm,
        private Money $fuelPricePerKm,
        private string $state,
        private int|null $ownerId = null,
        private array $transportTypes,
        private string $unit,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getVehicleId(): int|null
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

    public function getClosedAt(): DateTimeImmutable|null
    {
        return $this->closedAt;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getFirstTravelDate(): DateTimeImmutable|null
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

    public function getState(): string
    {
        return $this->state;
    }

    public function getOwnerId(): int|null
    {
        return $this->ownerId;
    }

    /** @return TransportType[] */
    public function getTransportTypes(): array
    {
        return $this->transportTypes;
    }

    /** @return string[] */
    public function getTransportTypePairs(): array
    {
        $types = [];

        foreach ($this->transportTypes as $type) {
            $types[$type->toString()] = $type->getLabel();
        }

        return $types;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }
}
