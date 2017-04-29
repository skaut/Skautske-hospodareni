<?php

namespace Model\Travel\Repositories;


use Model\Travel\Command;
use Model\Travel\CommandNotFoundException;

interface ICommandRepository
{

    /**
     * @param int $id
     * @return Command
     * @throws CommandNotFoundException
     */
    public function find(int $id): Command;

    public function countByVehicle(int $vehicleId): int;

    public function remove(Command $command): void;

    public function save(Command $command): void;

}
