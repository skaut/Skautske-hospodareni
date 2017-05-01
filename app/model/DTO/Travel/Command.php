<?php

namespace Model\DTO\Travel;

use Nette\SmartObject;

/**
 * @property-read int                       $id
 * @property-read int                       $unitId
 * @property-read int|NULL                  $vehicleId
 * @property-read int|NULL                  $contractId
 * @property-read string                    $purpose
 * @property-read string                    $place
 * @property-read string                    $passengers
 * @property-read float                     $fuelPrice
 * @property-read float                     $amortizationPerKm
 * @property-read string                    $note
 * @property-read \DateTimeImmutable|NULL   $closedAt
 * @property-read float                     $total
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

    /** @var int|NULL */
    private $contractId;

    /** @var string */
    private $purpose;

    /** @var string */
    private $place;

    /** @var string */
    private $passengers;

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

    public function __construct(
        int $id,
        int $unitId,
        ?int $vehicleId,
        ?int $contractId,
        string $purpose,
        string $place,
        string $passengers,
        float $fuelPrice,
        float $amortizationPerKm,
        string $note,
        ?\DateTimeImmutable $closedAt,
        float $total
    )
    {
        $this->id = $id;
        $this->unitId = $unitId;
        $this->vehicleId = $vehicleId;
        $this->contractId = $contractId;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->passengers = $passengers;
        $this->fuelPrice = $fuelPrice;
        $this->amortizationPerKm = $amortizationPerKm;
        $this->note = $note;
        $this->total = $total;
        $this->closedAt = $closedAt;
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

    public function getContractId(): ?int
    {
        return $this->contractId;
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

}
