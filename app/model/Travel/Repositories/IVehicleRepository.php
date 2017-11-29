<?php

namespace Model\Travel\Repositories;

use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

interface IVehicleRepository
{

    /**
     * @throws VehicleNotFoundException
     */
    public function get(int $id): Vehicle;

    /**
     * @param int[] $ids
     * @return Vehicle[]
     */
    public function findByIds(array $ids): array;

    /**
     * @param $unitId
     * @return Vehicle[]
     */
    public function getAll($unitId);

    /**
     * @param int $unitId
     * @return array
     */
    public function getPairs($unitId);

    public function save(Vehicle $vehicle): void;

    /**
     * Removes vehicle with specified ID
     * @param $vehicleId
     */
    public function remove($vehicleId): bool;

}
