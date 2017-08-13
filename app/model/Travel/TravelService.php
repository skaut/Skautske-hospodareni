<?php

namespace Model;

use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use Dibi\Row;
use Model\DTO\Travel as DTO;
use Model\DTO\Travel\Command\Travel as TravelDTO;
use Model\Travel\Command;
use Model\Travel\CommandNotFoundException;
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
class TravelService extends BaseService
{

    /** @var CommandTable */
    private $table;

    /** @var TravelTable */
    private $tableTravel;

    /** @var ContractTable */
    private $tableContract;

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
        ContractTable $tableContract,
        IVehicleRepository $vehicles,
        ICommandRepository $commands,
        IContractRepository $contracts,
        IUnitRepository $units
    )
    {
        parent::__construct();
        $this->table = $table;
        $this->tableTravel = $tableTravel;
        $this->tableContract = $tableContract;
        $this->vehicles = $vehicles;
        $this->commands = $commands;
        $this->contracts = $contracts;
        $this->units = $units;
    }

    public function isContractAccessible($contractId, $unit)
    {
        if (($contract = $this->getContract($contractId))) {
            return $contract->unit_id == $unit->ID ? TRUE : FALSE;
        }
        return FALSE;
    }

    /**     VEHICLES    */

    /**
     * vraci detail daného vozidla
     * @param int $vehicleId - ID vozidla
     * @return Vehicle
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

    public function getVehiclesPairs($unitId)
    {
        return $this->vehicles->getPairs($unitId);
    }

    /**
     * @param int $unitId
     * @return Travel\Vehicle[]
     */
    public function getAllVehicles($unitId)
    {
        return $this->vehicles->getAll($unitId);
    }


    public function createVehicle(string $type, int $unitId, ?int $subunitId, string $registration, float $consumption): void
    {
        $unit = $this->units->find($unitId, TRUE);

        $subunit = $subunitId !== NULL
            ? $this->units->find($subunitId, TRUE)
            : NULL;

        $vehicle = new Vehicle($type, $unit, $subunit, $registration, $consumption);
        $this->vehicles->save($vehicle);
    }

    public function removeVehicle($vehicleId)
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
     * @param int $commandId
     * @return DTO\Command\Travel[]
     * @throws CommandNotFoundException
     */
    public function getTravels(int $commandId): array
    {
        $travels = DTO\Command\TravelFactory::createList(
            $this->commands->find($commandId)
        );
        usort($travels, function (TravelDTO $a, TravelDTO $b) {
            return $a->getDetails()->getDate() <=> $b->getDetails()->getDate();
        });

        return $travels;
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
    public function getContract($contractId)
    {
        $cacheId = __FUNCTION__ . "_" . $contractId;
        if (!($res = $this->loadSes($cacheId))) {
            $res = $this->tableContract->get($contractId);
            $this->saveSes($cacheId, $res);
        }
        return $res;
    }

    public function getAllContracts($unitId)
    {
        return $this->tableContract->getAll($unitId);
    }

    public function getAllContractsPairs($unitId)
    {
        $data = $this->getAllContracts($unitId);
        $res = ["valid" => [], "past" => []];

        foreach ($data as $i) {
            if (is_null($i->end)) {
                $res["valid"][$i->id] = $i->driver_name;
            } else {
                if ($i->end->format("U") > time()) {
                    $res["valid"][$i->id] = $i->unit_person . " <=> " . $i->driver_name . " (platná do " . $i->end->format("j.n.Y") . ")";
                } else {
                    if ($i->end->format("Y") < date("Y") - 1) {#skoncila uz predloni
                        continue;
                    }
                    $res["past"][$i->id] = $i->unit_person . " <=> " . $i->driver_name . " (platná do " . $i->end->format("j.n.Y") . ")";
                }
            }
        }
        return $res;
    }

    public function addContract($values)
    {
        if (!$values['end'] && !is_null($values["start"])) {
            $values['end'] = date("Y-m-d", strtotime("+ 3 years", $values["start"]->getTimestamp()));
        } //nastavuje platnost smlouvy na 3 roky
        return $this->tableContract->add($values);
    }

    public function deleteContract($contractId)
    {
        return $this->tableContract->delete($contractId);
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
     * @param int $commandId
     */
    public function closeCommand($commandId): void
    {
        $this->table->changeState($commandId, date("Y-m-d H:i:s"));
    }

    /**
     * @param int $commandId
     */
    public function openCommand($commandId): void
    {
        $this->table->changeState($commandId, NULL);
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
