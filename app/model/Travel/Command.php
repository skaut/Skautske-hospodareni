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

    /** @var Vehicle */
    private $vehicle;

    /** @var Contract|NULL */
    private $contract;

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

    /** @var ArrayCollection|Travel[] */
    private $travels;

    public function __construct(
        int $unitId, Vehicle $vehicle, ?Contract $contract, string $purpose,
        string $place, string $passengers, float $fuelPrice, float $amortization, string $note
    )
    {
        $this->unitId = $unitId;
        $this->vehicle = $vehicle;
        $this->contract = $contract;
        $this->purpose = $purpose;
        $this->place = $place;
        $this->passengers = $passengers;
        $this->fuelPrice = $fuelPrice;
        $this->amortization = $amortization;
        $this->note = $note;
        $this->travels = new ArrayCollection();
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

    public function getId(): int
    {
        return $this->id;
    }

}
