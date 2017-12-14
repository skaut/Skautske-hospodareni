<?php

namespace Model\Travel\Repositories;

use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

interface IVehicleRepository
{

    /**
     * @throws VehicleNotFoundException
     */
    public function find(int $id): Vehicle;

    /**
     * @param int[] $ids
     * @return Vehicle[]
     */
    public function findByIds(array $ids): array;

    /**
     * @return Vehicle[]
     */
    public function findByUnit(int $unitId): array;

    /**
     * @return array<int,string> in format [id => label]
     */
    public function getPairs(int $unitId): array;

    public function save(Vehicle $vehicle): void;

    public function remove(Vehicle $vehicle): void;

}
