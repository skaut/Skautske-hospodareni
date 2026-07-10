<?php

declare(strict_types=1);

namespace App\Model\Travel;

use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Travel as DTO;
use App\Model\Travel\Repositories\ICommandRepository;
use App\Model\Travel\Repositories\IContractRepository;
use App\Model\Travel\Repositories\IVehicleRepository;
use App\Model\Travel\Travel\TransportType;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
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
    public function getVehicleDTO(int $id): ?DTO\Vehicle
    {
        try {
            return DTO\VehicleFactory::create(
                $this->vehicles->find($id),
            );
        } catch (VehicleNotFound) {
            return null;
        }
    }

    public function findVehicle(int $id): ?Vehicle
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

    public function getVehiclesByFilter(int $unitId): QueryBuilder
    {
        return $this->vehicles->findByFilter($unitId);
    }

    /** @throws VehicleLinkedRecord|VehicleNotFound */
    public function removeVehicle(int $vehicleId): void
    {
        if ($this->commands->countByVehicle($vehicleId) > 0) {
            throw new VehicleLinkedRecord('Cannot remove vehicle with linked commands');
        }

        $vehicle = $this->vehicles->find($vehicleId);
        $this->vehicles->remove($vehicle);
    }

    /**
     * Archives specified vehicle.
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
    public function getTravel(int $commandId, int $travelId): ?DTO\Command\Travel
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
    public function getContract(int $contractId): ?DTO\Contract
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
    public function getAllContractsPairs(int $unitId, ?int $includeContractId): array
    {
        $contracts = $this->contracts->findByUnit($unitId);

        $result = ['valid' => [], 'past' => []];

        $now = new DateTimeImmutable();

        foreach ($contracts as $contract) {
            $name = $contract->getPassenger()->getName();

            if ($contract->getUnitRepresentative() !== '') {
                $name = $contract->getUnitRepresentative().' <=> '.$name;
            }

            if ($contract->getUntil() !== null) {
                $name .= ' (platná do '.$contract->getUntil()->format('j.n.Y').')';
            }

            if ($contract->getUntil() === null || $contract->getUntil()->toNative() > $now) {
                $result['valid'][$contract->getId()] = $name;
            } elseif ($now->diff($contract->getUntil()->toNative())->y === 0 || $contract->getId() === $includeContractId) {
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

    public function getCommandDetail(int $id): ?DTO\Command
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
        ?int $contractId,
        ?Passenger $passenger,
        ?int $vehicleId,
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
        ?int $contractId,
        ?Passenger $passenger,
        ?int $vehicleId,
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

    /**
     * @param int[] $readableUnitIds
     *
     * @return DTO\Command[]
     */
    public function getVisibleUserCommands(array $readableUnitIds, int $userId): array
    {
        return array_map(function (Command $command) {
            return DTO\CommandFactory::create($command);
        }, $this->commands->findVisibleByUser($readableUnitIds, $userId));
    }

    public function getCommandsCount(int $vehicleId): int
    {
        return $this->commands->countByVehicle($vehicleId);
    }

    /**
     * vraci všechny přikazy navazane na smlouvu.
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
     * vraci všechny přikazy navazane na vozidlo.
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
     * @param int[] $readableUnitIds
     *
     * @return DTO\Command[]
     */
    public function getVisibleUserCommandsByVehicle(int $vehicleId, array $readableUnitIds, int $userId): array
    {
        return array_map(function (Command $command) {
            return DTO\CommandFactory::create($command);
        }, $this->commands->findVisibleByVehicleAndUser($vehicleId, $readableUnitIds, $userId));
    }

    /**
     * uzavře cestovní příkaz a nastavi cas uzavření.
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

    private function selectPassenger(?Passenger $passenger, ?int $contractId): Passenger
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
