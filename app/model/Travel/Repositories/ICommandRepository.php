<?php

namespace Model\Travel\Repositories;


interface ICommandRepository
{

    public function countByVehicle(int $vehicleId): int;

}
