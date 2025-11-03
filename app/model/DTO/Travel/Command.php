<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use DateTimeImmutable;
use Model\Travel\Passenger;
use Model\Travel\Travel\TransportType;
use Money\Money;
use Nette\SmartObject;

/**
 * @property int                    $id
 * @property int                    $unitId
 * @property int|null               $vehicleId
 * @property Passenger              $passenger
 * @property string                 $purpose
 * @property string                 $place
 * @property string                 $fellowPassengers
 * @property Money                  $fuelPrice
 * @property Money                  $amortizationPerKm
 * @property string                 $note
 * @property DateTimeImmutable|null $closedAt
 * @property Money                  $total
 * @property DateTimeImmutable|null $firstTravelDate
 * @property Money                  $pricePerKm
 * @property Money                  $fuelPricePerKm
 * @property string                 $state
 * @property TransportType[]        $transportTypes
 * @property string[]               $transportTypePairs
 * @property string                 $unit
 */
class Command
{
    use SmartObject;

    public const STATE_CLOSED = 'closed';
    public const STATE_IN_PROGRESS = 'in_progress';

    /** @param TransportType[] $transportTypes */
    public function __construct(
        private int $id,
        private int $unitId,
        private ?int $vehicleId,
        private Passenger $passenger,
        private string $purpose,
        private string $place,
        private string $fellowPassengers,
        private Money $fuelPrice,
        private Money $amortizationPerKm,
        private string $note,
        private ?DateTimeImmutable $closedAt,
        private Money $total,
        private ?DateTimeImmutable $firstTravelDate,
        private Money $pricePerKm,
        private Money $fuelPricePerKm,
        private string $state,
        private ?int $ownerId,
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

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getFirstTravelDate(): ?DateTimeImmutable
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

    public function getOwnerId(): ?int
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
