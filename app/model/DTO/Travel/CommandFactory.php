<?php

namespace Model\DTO\Travel;

use Model\Travel\Command as CommandEntity;

class CommandFactory
{

    public static function create(CommandEntity $command): Command
    {
        return new Command(
            $command->getId(),
            $command->getUnitId(),
            $command->getVehicleId(),
            $command->getContractId(),
            $command->getPurpose(),
            $command->getPlace(),
            $command->getPassengers(),
            $command->getFuelPrice(),
            $command->getAmortization(),
            $command->getNote(),
            $command->getClosedAt(),
            $command->calculateTotal()
        );
    }

}
