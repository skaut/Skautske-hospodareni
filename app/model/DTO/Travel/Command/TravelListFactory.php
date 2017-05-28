<?php

namespace Model\DTO\Travel\Command;

use Model\Travel\Command;
use Nette\StaticClass;

class TravelListFactory
{
    use StaticClass;

    /**
     * @param Command $command
     * @return Travel[]
     */
    public static function create(Command $command): array
    {
        return array_map(function(Command\Travel $travel) use ($command) {
            $id = $travel->getId();
            $details = $travel->getDetails();

            if($travel instanceof Command\VehicleTravel) {
                return new Travel($id, $details, $travel->getDistance(), $command->getPriceFor($travel));
            }

            if($travel instanceof Command\TransportTravel) {
                return new Travel($id, $details, NULL, $travel->getPrice());
            }

            throw new \RuntimeException("Invalid travel type ".get_class($travel));
        }, $command->getTravels());
    }

}
