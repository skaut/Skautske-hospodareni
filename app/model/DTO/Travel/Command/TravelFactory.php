<?php

namespace Model\DTO\Travel\Command;

use Model\Travel\Command;
use Nette\StaticClass;

class TravelFactory
{
    use StaticClass;

    /**
     * @param Command $command
     * @return Travel[]
     */
    public static function createList(Command $command): array
    {
        return array_map(function(Command\Travel $travel) use ($command) {
            return self::createSingle($travel, $command);
        }, $command->getTravels());
    }

    public static function create(Command $command, int $travelId): ?Travel
    {
        foreach($command->getTravels() as $travel) {
            if($travel->getId() === $travelId) {
                return self::createSingle($travel, $command);
            }
        }

        return NULL;
    }

    private static function createSingle(Command\Travel $travel, Command $command): Travel
    {
        $id = $travel->getId();
        $details = $travel->getDetails();

        if ($travel instanceof Command\VehicleTravel) {
            return new Travel($id, $details, $travel->getDistance(), $command->getPriceFor($travel));
        }

        if ($travel instanceof Command\TransportTravel) {
            return new Travel($id, $details, NULL, $travel->getPrice());
        }

        throw new \RuntimeException("Invalid travel type " . get_class($travel));
    }

}
