<?php

declare(strict_types=1);

namespace Model\Travel\Repositories;

use Doctrine\ORM\QueryBuilder;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFound;

interface IVehicleRepository
{
    /** @throws VehicleNotFound */
    public function find(int $id): Vehicle;

    /**
     * @param int[] $ids
     *
     * @return Vehicle[]
     */
    public function findByIds(array $ids): array;

    public function findByFilter(): QueryBuilder;

    /** @return Vehicle[] */
    public function findByUnit(int $unitId): array;

    public function save(Vehicle $vehicle): void;

    public function remove(Vehicle $vehicle): void;
}
