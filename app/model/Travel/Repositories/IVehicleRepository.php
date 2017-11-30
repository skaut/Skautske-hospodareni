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
     * @return Vehicle[]
     */
    public function getAll(int $unitId): array;

    /**
     * @return array in format [id => label]
     */
    public function getPairs(int $unitId): array;

    public function save(Vehicle $vehicle): void;

    /**
     * Removes vehicle with specified ID
     */
    public function remove(int $vehicleId): bool;

}
