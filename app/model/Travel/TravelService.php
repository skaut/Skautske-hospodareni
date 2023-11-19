<?php

declare(strict_types=1);

namespace Model;

use Cake\Chronos\ChronosDate;
use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use DateTimeImmutable;
use InvalidArgumentException;
use Model\Common\Services\QueryBus;
use Model\DTO\Travel as DTO;
use Model\Travel\Command;
use Model\Travel\CommandNotFound;
use Model\Travel\Contract;
use Model\Travel\ContractNotFound;
use Model\Travel\Passenger;
use Model\Travel\Repositories\ICommandRepository;
use Model\Travel\Repositories\IContractRepository;
use Model\Travel\Repositories\IVehicleRepository;
use Model\Travel\Travel\TransportType;
use Model\Travel\TravelNotFound;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFound;
use Model\Unit\Repositories\IUnitRepository;
use Model\Utils\MoneyFactory;
use Money\Money;

use function array_map;
use function array_merge;

class TravelService
{
    public function __construct(
        private IVehicleRepository $vehicles,
        private ICommandRepository $commands,
        private IContractRepository $contracts,
        private IUnitRepository $units,
        protected QueryBus $queryBus,
    ) {
    }

    /**     VEHICLES    */
    public function getVehicleDTO(int $id): DTO\Vehicle|null
    {
        try {
            return DTO\VehicleFactory::create(
                $this->vehicles->find($id),
            );
        } catch (VehicleNotFound) {
            return null;
        }
    }

    public function findVehicle(int $id): Vehicle|null
    {
        try {
            return $this->vehicles->find($id);
        } catch (VehicleNotFound) {
            return null;
        }
    }

    /** @return string[] */
    public function getVehiclesPairs(int $unitId): array
    {
        $pairs = [];

        foreach ($this->vehicles->findByUnit($unitId) as $vehicle) {
            $pairs[$vehicle->getId()] = $vehicle->getLabel();
        }

        return $pairs;
    }

    /** @return DTO\Vehicle[] */
    public function getAllVehicles(int $unitId): array
    {
        return array_map(
            [DTO\VehicleFactory::class, 'create'],
            $this->vehicles->findByUnit($unitId),
        );
    }

    public function removeVehicle(int $vehicleId): bool
    {
        if ($this->commands->countByVehicle($vehicleId) > 0) {
            return false; //nelze mazat vozidlo s navazanými příkazy
        }

        try {
            $vehicle = $this->vehicles->find($vehicleId);

            $this->vehicles->remove($vehicle);

            return true;
        } catch (VehicleNotFound) {
            return false;
        }
    }

    /**
     * Archives specified vehicle
     */
    public function archiveVehicle(int $vehicleId): void
    {
        $vehicle = $this->vehicles->find($vehicleId);

        if ($vehicle->isArchived()) {
            return;
        }

        $vehicle->archive();
        $this->vehicles->save($vehicle);
    }

    /**     TRAVELS    */
    public function getTravel(int $commandId, int $travelId): DTO\Command\Travel|null
    {
        $command = $this->commands->find($commandId);

        return DTO\Command\TravelFactory::create($command, $travelId);
    }

    /**
     * @return DTO\Command\Travel[]
     *
     * @throws CommandNotFound
     */
    public function getTravels(int $commandId): array
    {
        return DTO\Command\TravelFactory::createList(
            $this->commands->find($commandId),
        );
    }

    public function addTravel(int $commandId, TransportType $transportType, ChronosDate $date, string $startPlace, string $endPlace, float $distanceOrPrice): void
    {
        $command = $this->commands->find($commandId);

        $details = new Command\TravelDetails($date, $transportType, $startPlace, $endPlace);

        if ($transportType->hasFuel()) {
            $command->addVehicleTravel($distanceOrPrice, $details);
        } else {
            $command->addTransportTravel(MoneyFactory::fromFloat($distanceOrPrice), $details);
        }

        $this->commands->save($command);
    }

    public function updateTravel(
        int $commandId,
        int $travelId,
        float $distanceOrPrice,
        ChronosDate $date,
        TransportType $transportType,
        string $startPlace,
        string $endPlace,
    ): void {
        $details = new Command\TravelDetails($date, $transportType, $startPlace, $endPlace);

        $command = $this->commands->find($commandId);

        try {
            if ($transportType->hasFuel()) {
                $command->updateVehicleTravel($travelId, $distanceOrPrice, $details);
            } else {
                $command->updateTransportTravel($travelId, MoneyFactory::fromFloat($distanceOrPrice), $details);
            }

            $this->commands->save($command);
        } catch (TravelNotFound) {
        }
    }

    public function removeTravel(int $commandId, int $travelId): void
    {
        $command = $this->commands->find($commandId);
        $command->removeTravel($travelId);

        $this->commands->save($command);
    }

    /**     CONTRACTS    */
    public function getContract(int $contractId): DTO\Contract|null
    {
        try {
            return DTO\ContractFactory::create(
                $this->contracts->find($contractId),
            );
        } catch (ContractNotFound) {
            return null;
        }
    }

    /** @return DTO\Contract[] */
    public function getAllContracts(int $unitId): array
    {
        return array_map(
            [DTO\ContractFactory::class, 'create'],
            $this->contracts->findByUnit($unitId),
        );
    }

    /** @return string[][] */
    public function getAllContractsPairs(int $unitId, int|null $includeContractId): array
    {
        $contracts = $this->contracts->findByUnit($unitId);

        $result = ['valid' => [], 'past' => []];

        $now = new DateTimeImmutable();

        foreach ($contracts as $contract) {
            $name = $contract->getPassenger()->getName();

            if ($contract->getUnitRepresentative() !== '') {
                $name = $contract->getUnitRepresentative() . ' <=> ' . $name;
            }

            if ($contract->getUntil() !== null) {
                $name .= ' (platná do ' . $contract->getUntil()->format('j.n.Y') . ')';
            }

            if ($contract->getUntil() === null || $contract->getUntil() > $now) {
                $result['valid'][$contract->getId()] = $name;
            } elseif ($now->diff($contract->getUntil())->y === 0 || $contract->getId() === $includeContractId) {
                $result['past'][$contract->getId()] = $name;
            }
        }

        return $result;
    }

    public function createContract(int $unitId, string $unitRepresentative, ChronosDate $since, Contract\Passenger $passenger): void
    {
        $unit = $this->units->find($unitId);

        $contract = new Contract($unit, $unitRepresentative, $since, $passenger);

        $this->contracts->save($contract);
    }

    public function deleteContract(int $contractId): void
    {
        try {
            $contract = $this->contracts->find($contractId);
            $this->contracts->remove($contract);
        } catch (ContractNotFound) {
        }
    }

    public function getCommandDetail(int $id): DTO\Command|null
    {
        try {
            return DTO\CommandFactory::create($this->commands->find($id));
        } catch (CommandNotFound) {
            return null;
        }
    }

    /** @param list<TransportType> $types */
    public function addCommand(
        int $unitId,
        int|null $contractId,
        Passenger|null $passenger,
        int|null $vehicleId,
        string $purpose,
        string $place,
        string $passengers,
        Money $fuelPrice,
        Money $amortization,
        string $note,
        array $types,
        int $ownerId,
        string $unit,
    ): void {
        $vehicle = $vehicleId !== null
            ? $this->vehicles->find($vehicleId)
            : null;

        $command = new Command(
            $unitId,
            $vehicle,
            $this->selectPassenger($passenger, $contractId),
            $purpose,
            $place,
            $passengers,
            $fuelPrice,
            $amortization,
            $note,
            $ownerId,
            $types,
            $unit,
        );

        $this->commands->save($command);
    }

    /** @param list<TransportType> $types */
    public function updateCommand(
        int $id,
        int|null $contractId,
        Passenger|null $passenger,
        int|null $vehicleId,
        string $purpose,
        string $place,
        string $passengers,
        Money $fuelPrice,
        Money $amortization,
        string $note,
        array $types,
        string $unit,
    ): void {
        $command = $this->commands->find($id);

        $vehicle = $vehicleId !== null
            ? $this->vehicles->find($vehicleId)
            : null;

        $command->update(
            $vehicle,
            $this->selectPassenger($passenger, $contractId),
            $purpose,
            $place,
            $passengers,
            $fuelPrice,
            $amortization,
            $note,
            array_merge($types, $command->getUsedTransportTypes()),
            $unit,
        );

        $this->commands->save($command);
    }

    /**
     * @param int[] $ids
     *
     * @return DTO\Vehicle[]
     */
    public function findVehiclesByIds(array $ids): array
    {
        return ArrayType::mapByCallback(
            $this->vehicles->findByIds($ids),
            function (KeyValuePair $pair) {
                return new KeyValuePair($pair->getKey(), DTO\VehicleFactory::create($pair->getValue()));
            },
        );
    }

    /** @return DTO\Command[] */
    public function getAllCommands(int $unitId): array
    {
        return array_map(function (Command $command) {
            return DTO\CommandFactory::create($command);
        }, $this->commands->findByUnit($unitId));
    }

    /** @return DTO\Command[] */
    public function getAllUserCommands(int $unitId, int $userId): array
    {
        return array_map(function (Command $command) {
            return DTO\CommandFactory::create($command);
        }, $this->commands->findByUnitAndUser($unitId, $userId));
    }

    public function getCommandsCount(int $vehicleId): int
    {
        return $this->commands->countByVehicle($vehicleId);
    }

    /**
     * vraci všechny přikazy navazane na smlouvu
     *
     * @return DTO\Command[]
     */
    public function getAllCommandsByContract(int $contractId): array
    {
        return array_map(
            [DTO\CommandFactory::class, 'create'],
            $this->commands->findByContract($contractId),
        );
    }

    /**
     * vraci všechny přikazy navazane na vozidlo
     *
     * @return DTO\Command[]
     */
    public function getAllCommandsByVehicle(int $vehicleId): array
    {
        return array_map(function (Command $command) {
            return DTO\CommandFactory::create($command);
        }, $this->commands->findByVehicle($vehicleId));
    }

    /**
     * uzavře cestovní příkaz a nastavi cas uzavření
     */
    public function closeCommand(int $commandId): void
    {
        $command = $this->commands->find($commandId);

        $command->close(new DateTimeImmutable());

        $this->commands->save($command);
    }

    public function openCommand(int $commandId): void
    {
        $command = $this->commands->find($commandId);

        $command->open();

        $this->commands->save($command);
    }

    public function deleteCommand(int $commandId): void
    {
        $command = $this->commands->find($commandId);
        $this->commands->remove($command);
    }

    private function selectPassenger(Passenger|null $passenger, int|null $contractId): Passenger
    {
        if (
            ($passenger === null && $contractId === null)
            || ($passenger !== null && $contractId !== null)
        ) {
            throw new InvalidArgumentException('Either passenger or contract must be specified');
        }

        return $contractId === null
            ? $passenger
            : Passenger::fromContract($this->contracts->find($contractId));
    }
}
