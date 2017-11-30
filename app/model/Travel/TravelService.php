<?php

namespace Model;

use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use Dibi\Row;
use Model\DTO\Travel as DTO;
use Model\DTO\Travel\Command\Travel as TravelDTO;
use Model\Travel\Command;
use Model\Travel\CommandNotFoundException;
use Model\Travel\Contract;
use Model\Travel\ContractNotFoundException;
use Model\Travel\Passenger;
use Model\Travel\Repositories\ICommandRepository;
use Model\Travel\Repositories\IContractRepository;
use Model\Travel\Repositories\IVehicleRepository;
use Model\Travel\TravelNotFoundException;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;
use Model\Unit\Repositories\IUnitRepository;
use Model\Utils\MoneyFactory;
use Money\Money;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa cestovních příkazů
 */
class TravelService
{

    /** @var CommandTable */
    private $table;

    /** @var TravelTable */
    private $tableTravel;

    /** @var IVehicleRepository */
    private $vehicles;

    /** @var ICommandRepository */
    private $commands;

    /** @var IContractRepository */
    private $contracts;

    /** @var IUnitRepository */
    private $units;

    public function __construct(
        CommandTable $table,
        TravelTable $tableTravel,
        IVehicleRepository $vehicles,
        ICommandRepository $commands,
        IContractRepository $contracts,
        IUnitRepository $units
    )
    {
        $this->table = $table;
        $this->tableTravel = $tableTravel;
        $this->vehicles = $vehicles;
        $this->commands = $commands;
        $this->contracts = $contracts;
        $this->units = $units;
    }

    /**     VEHICLES    */

    /**
     * vraci detail daného vozidla
     */
    public function getVehicle(int $vehicleId): Vehicle
    {
        return $this->vehicles->get($vehicleId);
    }

    public function findVehicle(int $id): ?Vehicle
    {
        try {
            return $this->vehicles->get($id);
        } catch (VehicleNotFoundException $e) {
            return NULL;
        }
    }

    public function getVehiclesPairs(int $unitId): array
    {
        return $this->vehicles->getPairs($unitId);
    }

    /**
     * @return Travel\Vehicle[]
     */
    public function getAllVehicles(int $unitId): array
    {
        return $this->vehicles->getAll($unitId);
    }


    public function createVehicle(string $type, int $unitId, ?int $subunitId, string $registration, float $consumption): void
    {
        $unit = $this->units->find($unitId);

        $subunit = $subunitId !== NULL
            ? $this->units->find($subunitId)
            : NULL;

        $vehicle = new Vehicle($type, $unit, $subunit, $registration, $consumption);
        $this->vehicles->save($vehicle);
    }

    public function removeVehicle(int $vehicleId): bool
    {
        if ($this->commands->countByVehicle($vehicleId) > 0) { //nelze mazat vozidlo s navazanými příkazy
            return FALSE;
        }
        return $this->vehicles->remove($vehicleId);
    }

    /**
     * Archives specified vehicle
     * @param int $vehicleId
     */
    public function archiveVehicle(int $vehicleId): void
    {
        $vehicle = $this->getVehicle($vehicleId);

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
     * @throws CommandNotFoundException
     */
    public function getTravels(int $commandId): array
    {
        return DTO\Command\TravelFactory::createList(
            $this->commands->find($commandId)
        );
    }

    public function addTravel(int $commandId, string $type, \DateTimeImmutable $date, string $startPlace, string $endPlace, float $distanceOrPrice): void
    {
        $command = $this->commands->find($commandId);

        $details = new Command\TravelDetails($date, $type, $startPlace, $endPlace);

        if ($this->hasFuel($type)) {
            $command->addVehicleTravel($distanceOrPrice, $details);
        } else {
            $command->addTransportTravel(MoneyFactory::fromFloat($distanceOrPrice), $details);
        }

        $this->commands->save($command);
    }

    public function updateTravel(int $commandId, int $travelId, float $distanceOrPrice, Command\TravelDetails $details): void
    {
        $command = $this->commands->find($commandId);

        try {
            if ($this->hasFuel($details->getTransportType())) {
                $command->updateVehicleTravel($travelId, $distanceOrPrice, $details);
            } else {
                $command->updateTransportTravel($travelId, MoneyFactory::fromFloat($distanceOrPrice), $details);
            }
            $this->commands->save($command);
        } catch (TravelNotFoundException $e) {
        }
    }

    public function removeTravel(int $commandId, int $travelId): void
    {
        $command = $this->commands->find($commandId);
        $command->removeTravel($travelId);

        $this->commands->save($command);
    }

    public function getTravelTypes($pairs = FALSE)
    {
        return $this->tableTravel->getTypes($pairs);
    }

    /**
     * @param int[] $commandIds
     * @return string[]
     */
    public function getTypes(array $commandIds): array
    {
        return $this->table->getTypes($commandIds);
    }

    /**     CONTRACTS    */
    public function getContract(int $contractId): ?DTO\Contract
    {
        try {
            return DTO\ContractFactory::create(
                $this->contracts->find($contractId)
            );
        } catch (ContractNotFoundException $e) {
            return NULL;
        }
    }

    /**
     * @param int $unitId
     * @return DTO\Contract[]
     */
    public function getAllContracts($unitId): array
    {
        return array_map(
            [DTO\ContractFactory::class, 'create'],
            $this->contracts->findByUnit($unitId)
        );
    }

    /**
     * @return string[][]
     */
    public function getAllContractsPairs(int $unitId, ?int $includeContractId): array
    {
        $contracts = $this->contracts->findByUnit($unitId);

        $result = ["valid" => [], "past" => []];

        $now = new \DateTimeImmutable();

        foreach($contracts as $contract) {
            $name = $contract->getPassenger()->getName();

            if($contract->getUnitRepresentative() !== '') {
                $name = $contract->getUnitRepresentative() . ' <=> ' . $name;
            }

            if($contract->getUntil() !== NULL) {
                $name .= ' (platná do ' . $contract->getUntil()->format('j.n.Y') . ')';
            }

            if($contract->getUntil() === NULL || $contract->getUntil() > $now) {
                $result['valid'][$contract->getId()] = $name;
            } elseif($now->diff($contract->getUntil())->y === 0 || $contract->getId() === $includeContractId) {
                $result['past'][$contract->getId()] = $name;
            }
        }

        return $result;
    }

    public function createContract(int $unitId, string $unitRepresentative, \DateTimeImmutable $since, Contract\Passenger $passenger): void
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
        } catch (ContractNotFoundException $e) {
        }
    }

    /*     COMMANDS    */

    public function getCommandDetail(int $id): ?DTO\Command
    {
        try {
            return DTO\CommandFactory::create($this->commands->find($id));
        } catch (CommandNotFoundException $e) {
            return NULL;
        }
    }

    /**
     * @param int $commandId
     * @return string[]
     */
    public function getUsedTransportTypes(int $commandId): array
    {
        $command = $this->commands->find($commandId);

        return $command->getUsedTransportTypes();
    }

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
        array $types
    ): void
    {
        $vehicle = $vehicleId !== NULL
            ? $this->vehicles->get($vehicleId)
            : NULL;

        $command = new Command(
            $unitId,
            $vehicle,
            $this->selectPassenger($passenger, $contractId),
            $purpose,
            $place,
            $passengers,
            $fuelPrice,
            $amortization,
            $note
        );

        $this->commands->save($command);
        $this->table->updateTypes($command->getId(), $types);
    }

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
        array $types
    ): void
    {
        $command = $this->commands->find($id);

        $vehicle = $vehicleId !== NULL
            ? $this->vehicles->get($vehicleId)
            : NULL;

        $command->update(
            $vehicle,
            $this->selectPassenger($passenger, $contractId),
            $purpose,
            $place,
            $passengers,
            $fuelPrice,
            $amortization,
            $note
        );

        $this->commands->save($command);

        foreach ($command->getUsedTransportTypes() as $type) {
            if (!in_array($type, $types, TRUE)) {
                $types[] = $type;
            }
        }

        $this->table->updateTypes($id, $types);
    }

    /**
     * @param int[] $ids
     * @return DTO\Vehicle[]
     */
    public function findVehiclesByIds(array $ids): array
    {
        return ArrayType::mapByCallback(
            $this->vehicles->findByIds($ids),
            function (KeyValuePair $pair) {
                return new KeyValuePair($pair->getKey(), DTO\VehicleFactory::create($pair->getValue()));
            }
        );
    }


    /**
     * @return DTO\Command[]
     */
    public function getAllCommands(int $unitId): array
    {
        return array_map(function (Command $command) {
            return DTO\CommandFactory::create($command);
        }, $this->commands->findByUnit($unitId));
    }

    public function getCommandsCount(int $vehicleId): int
    {
        return $this->commands->countByVehicle($vehicleId);
    }

    /**
     * vraci všechny přikazy navazane na smlouvu
     * @param int $unitId
     * @param int $contractId
     * @return Row[]
     */
    public function getAllCommandsByContract($unitId, $contractId)
    {
        return $this->table->getAllByContract($unitId, $contractId);
    }

    /**
     * vraci všechny přikazy navazane na vozidlo
     * @return DTO\Command[]
     */
    public function getAllCommandsByVehicle(int $vehicleId)
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

        $command->close(new \DateTimeImmutable());

        $this->commands->save($command);
    }

    public function openCommand(int $commandId): void
    {
        $command = $this->commands->find($commandId);

        $command->open();

        $this->commands->save($command);
    }

    /**
     * @param int $commandId
     */
    public function deleteCommand(int $commandId): void
    {
        $command = $this->commands->find($commandId);
        $this->commands->remove($command);
    }

    public function getCommandTypes($commandId)
    {
        return $this->table->getCommandTypes($commandId);
    }

    private function selectPassenger(?Passenger $passenger, ?int $contractId): Passenger
    {
        if (($passenger === NULL && $contractId === NULL)
            || ($passenger !== NULL && $contractId !== NULL)
        ) {
            throw new \InvalidArgumentException("Either passenger or contract must be specified");
        }

        return $contractId === NULL
            ? $passenger
            : Passenger::fromContract($this->contracts->find($contractId));
    }

    private function hasFuel(string $type): bool
    {
        $type = $this->tableTravel->getTypes()[$type] ?? NULL;

        if ($type === NULL) {
            throw new \InvalidArgumentException("Type $type not found");
        }

        return $type["hasFuel"];
    }

}
