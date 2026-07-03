<?php

declare(strict_types=1);

namespace App\Model\Travel\Repositories;

use App\Model\Travel\Command;
use App\Model\Travel\CommandNotFound;

interface ICommandRepository
{
    /** @throws CommandNotFound */
    public function find(int $id): Command;

    /** @return Command[] */
    public function findByUnit(int $unitId): array;

    /** @return Command[] */
    public function findByUnitAndUser(int $unitId, int $userId): array;

    /** @return Command[] */
    public function findByVehicle(int $vehicleId): array;

    /** @return Command[] */
    public function findByContract(int $contractId): array;

    public function countByVehicle(int $vehicleId): int;

    public function remove(Command $command): void;

    public function save(Command $command): void;
}
