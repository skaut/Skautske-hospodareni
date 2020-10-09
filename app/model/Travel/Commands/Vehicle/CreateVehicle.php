<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Vehicle;

use Model\Travel\Handlers\Vehicle\CreateVehicleHandler;

/**
 * @see CreateVehicleHandler
 */
final class CreateVehicle
{
    private string $type;

    private int $unitId;

    /** @var int|NULL */
    private $subunitId;

    private string $registration;

    private float $consumption;

    private int $userId;

    public function __construct(
        string $type,
        int $unitId,
        ?int $subunitId,
        string $registration,
        float $consumption,
        int $userId
    ) {
        $this->type         = $type;
        $this->unitId       = $unitId;
        $this->subunitId    = $subunitId;
        $this->registration = $registration;
        $this->consumption  = $consumption;
        $this->userId       = $userId;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getSubunitId() : ?int
    {
        return $this->subunitId;
    }

    public function getRegistration() : string
    {
        return $this->registration;
    }

    public function getConsumption() : float
    {
        return $this->consumption;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }
}
