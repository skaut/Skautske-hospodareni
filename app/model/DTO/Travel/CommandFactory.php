<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Model\Travel\Command as CommandEntity;

class CommandFactory
{
    public static function create(CommandEntity $command) : Command
    {
        return new Command(
            $command->getId(),
            $command->getUnitId(),
            $command->getVehicleId(),
            $command->getPassenger(),
            $command->getPurpose(),
            $command->getPlace(),
            $command->getFellowPassengers(),
            $command->getFuelPrice(),
            $command->getAmortization(),
            $command->getNote(),
            $command->getClosedAt(),
            $command->calculateTotal(),
            $command->getFirstTravelDate(),
            $command->getPricePerKm(),
            $command->getFuelPricePerKm(),
            $command->getClosedAt() !== null ? Command::STATE_CLOSED : Command::STATE_IN_PROGRESS
        );
    }
}
