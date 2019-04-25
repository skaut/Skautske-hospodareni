<?php

declare(strict_types=1);

namespace Model\Travel\Repositories;

use Model\Travel\Command;
use Model\Travel\CommandNotFound;

interface ICommandRepository
{
    /**
     * @throws CommandNotFound
     */
    public function find(int $id) : Command;

    /**
     * @return Command[]
     */
    public function findByUnit(int $unitId) : array;

    /**
     * @return Command[]
     */
    public function findByUnitAndUser(int $unitId, int $userId) : array;

    /**
     * @return Command[]
     */
    public function findByVehicle(int $vehicleId) : array;

    /**
     * @return Command[]
     */
    public function findByContract(int $contractId) : array;

    public function countByVehicle(int $vehicleId) : int;

    public function remove(Command $command) : void;

    public function save(Command $command) : void;
}
