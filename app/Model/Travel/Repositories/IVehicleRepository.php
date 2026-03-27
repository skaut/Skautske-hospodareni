<?php

declare(strict_types=1);

namespace App\Model\Travel\Repositories;

use App\Model\Travel\Vehicle;
use App\Model\Travel\VehicleNotFound;
use Doctrine\ORM\QueryBuilder;

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
