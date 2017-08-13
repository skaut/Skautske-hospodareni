<?php

namespace Model\Travel\Repositories;


use Model\Travel\Command;
use Model\Travel\CommandNotFoundException;
use Model\Travel\Vehicle;

interface ICommandRepository
{

    /**
     * @param int $id
     * @return Command
     * @throws CommandNotFoundException
     */
    public function find(int $id): Command;

    /**
     * @param int $unitId
     * @return Vehicle[]
     */
    public function findByUnit(int $unitId): array;


    /**
     * @return Command[]
     */
    public function findByVehicle(int $vehicleId): array;


    public function countByVehicle(int $vehicleId): int;

    public function remove(Command $command): void;

    public function save(Command $command): void;

}
