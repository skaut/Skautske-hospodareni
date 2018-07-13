<?php

declare(strict_types=1);

namespace Model\DTO\Travel\Command;

use Model\Travel\Command;
use Nette\StaticClass;
use function array_map;
use function get_class;
use function usort;

class TravelFactory
{
    use StaticClass;

    /**
     * @return Travel[]
     */
    public static function createList(Command $command) : array
    {
        $dtos = array_map(
            function (Command\Travel $travel) use ($command) {
                return self::createSingle($travel, $command);
            },
            $command->getTravels()
        );

        usort(
            $dtos,
            function (Travel $a, Travel $b) {
                $result = $a->getDetails()->getDate() <=> $b->getDetails()->getDate();

                return $result === 0 ? $a->getId() <=> $b->getId() : $result;
            }
        );

        return $dtos;
    }

    public static function create(Command $command, int $travelId) : ?Travel
    {
        foreach ($command->getTravels() as $travel) {
            if ($travel->getId() === $travelId) {
                return self::createSingle($travel, $command);
            }
        }

        return null;
    }

    private static function createSingle(Command\Travel $travel, Command $command) : Travel
    {
        $id      = $travel->getId();
        $details = $travel->getDetails();

        if ($travel instanceof Command\VehicleTravel) {
            return new Travel($id, $details, $travel->getDistance(), $command->getPriceFor($travel));
        }

        if ($travel instanceof Command\TransportTravel) {
            return new Travel($id, $details, null, $travel->getPrice());
        }

        throw new \RuntimeException('Invalid travel type ' . get_class($travel));
    }
}
