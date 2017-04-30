<?php

namespace Model;

use Dibi\Row;
use Model\Travel\Command;
use Model\Travel\Repositories\ICommandRepository;
use Model\Travel\Repositories\IContractRepository;
use Model\Travel\Repositories\IVehicleRepository;
use Model\Travel\TransportType;
use Model\Travel\Vehicle;

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

    public function __construct(
        CommandTable $table,
        TravelTable $tableTravel,
        ContractTable $tableContract,
        IVehicleRepository $vehicles,
        ICommandRepository $commands,
        IContractRepository $contracts
    )
    {
        parent::__construct();
        $this->table = $table;
        $this->tableTravel = $tableTravel;
        $this->tableContract = $tableContract;
        $this->vehicles = $vehicles;
        $this->commands = $commands;
        $this->contracts = $contracts;
    }

    public function isContractAccessible($contractId, $unit)
    {
        if (($contract = $this->getContract($contractId))) {
            return $contract->unit_id == $unit->ID ? TRUE : FALSE;
        }
        return FALSE;
    }

    public function isCommandAccessible($commandId, $unit)
    {
        if (($command = $this->getCommand($commandId))) {
            return $command->unit_id == $unit->ID ? TRUE : FALSE;
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

    /**
     * @param array $data
     */
    public function addVehicle($data): void
    {
        $vehicle = new Vehicle($data['type'], $data['unit_id'], $data['registration'], $data['consumption']);
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
    public function getTravel($commandId)
    {
        return $this->tableTravel->get($commandId);
    }

    public function getTravels($commandId)
    {
        return $this->tableTravel->getAll($commandId);
    }

    public function addTravel($data)
    {
        $command = $this->commands->find((int)$data["command_id"]);

        $types = $this->tableTravel->getTypes();

        $type = $types[$data["type"]] ?? NULL;

        if($type === NULL) {
            throw new \InvalidArgumentException("Type {$data['type']} not find");
        }

        $transportType = new TransportType($type["type"], (bool)$type["hasFuel"]);
        $command->createTravel(
            new \DateTimeImmutable(),
            $data["distance"],
            $transportType,
            $data["start_place"],
            $data["end_place"]
        );

        $this->commands->save($command);
    }

    public function updateTravel($data, $tId)
    {
        return $this->tableTravel->update($data, $tId);
    }

    public function deleteTravel($travelId)
    {
        return $this->tableTravel->delete($travelId);
    }

    public function getTravelTypes($pairs = FALSE)
    {
        return $this->tableTravel->getTypes($pairs);
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

    /**     COMMANDS    */
    public function getCommand($commandId)
    {
        $cacheId = __FUNCTION__ . "_" . $commandId;
        if (!($res = $this->loadSes($cacheId))) {
            $res = $this->table->get($commandId);
            $this->saveSes($cacheId, $res);
        }
        return $res;
    }

    public function addCommand(
        int $unitId,
        ?int $contractId,
        int $vehicleId,
        string $purpose,
        string $place,
        string $passengers,
        float $fuelPrice,
        float $amortization,
        string $note,
        array $types
    ): void
    {
        $contract = $contractId !== NULL
                  ? $this->contracts->find($contractId)
                  : NULL;

        $vehicle = $this->vehicles->get($vehicleId);

        $command = new Command(
            $unitId,
            $vehicle,
            $contract,
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

    public function updateCommand($v, $unit, $id)
    {
        if (!$this->isContractAccessible($v['contract_id'], $unit)) {
            return FALSE; //neoprávěný přístup
        }
        $types = $v["type"];
        unset($v["type"]);
        $status = $this->table->update($v, $id);
        return $status || $this->table->updateTypes($id, $types);
    }

    public function getAllCommands($unitId)
    {
        return $this->table->getAll($unitId);
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
     * @param int $unitId
     * @param int $vehicleId
     * @return Row[]
     */
    public function getAllCommandsByVehicle($unitId, $vehicleId)
    {
        return $this->table->getAllByVehicle($unitId, $vehicleId);
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

}
