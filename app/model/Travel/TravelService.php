<?php

/**
 * @author Hána František
 * správa cestovních příkazů
 */
class TravelService extends BaseService {

    protected $tableTravel;
    protected $tableContract;
    protected $tableVehicle;

    public function __construct() {
        /** @var TravelTable */
        $this->table = new CommandTable();
        $this->tableTravel = new TravelTable();
        $this->tableContract = new ContractTable();
        $this->tableVehicle = new VehicleTable();
    }

    public function isMyContract($contractId, $unit) {
        return $this->getContract($contractId)->unit_id == $unit->ID ? TRUE : FALSE;
    }

    /**     VEHICLES    */
    
    /**
     * vraci detail daného vozidla
     * @param type $vehicleId - ID vozidla
     * @param type $withDeleted - i smazana vozidla?
     * @return type
     */
    public function getVehicle($vehicleId, $withDeleted = false) {
        return $this->tableVehicle->get($vehicleId, $withDeleted);
    }

    public function getVehiclesPairs($unitId) {
        return $this->tableVehicle->getPairs($unitId);
    }

    public function getAllVehicles($unitId) {
        return $this->tableVehicle->getAll($unitId);
    }

    public function addVehicle($data) {
        return $this->tableVehicle->add($data);
    }

    public function removeVehicle($vehicleId) {
        return $this->tableVehicle->remove($vehicleId);
    }

    /**     TRAVELS    */
    public function getTravel($commandId) {
        return $this->tableTravel->get($commandId);
    }

    public function getTravels($commandId) {
        return $this->tableTravel->getAll($commandId);
    }

    public function addTravel($data) {
        return $this->tableTravel->add($data);
    }

    public function deleteTravel($travelId) {
        return $this->tableTravel->delete($travelId);
    }

    /**     CONTRACTS    */
    public function getContract($contractId) {
        return $this->tableContract->get($contractId);
    }

    public function getAllContracts($unitId) {
        return $this->tableContract->getAll($unitId);
    }

    public function getAllContractsPairs($unitId) {
        $data = $this->getAllContracts($unitId);
        $res = array();
        foreach ($data as $i) {
            $res[$i->id] = $i->unit_person . " <=> " . $i->driver_name;
        }
        return $res;
    }

    public function addContract($values) {
        if (!$values['end'])
            $values['end'] = date("Y-m-d", strtotime("+ 3 years", $values['start']->getTimestamp())); //nastavuje platnost smlouvy na 3 roky
        return $this->tableContract->add($values);
    }

    /**     COMMANDS    */
    public function getCommand($commandId) {
        return $this->table->get($commandId);
    }

    public function addCommand($v, $unit) {
        if (!$this->isMyContract($v['contract_id'], $unit))
            return false; //neoprávěný přístup
        return $this->table->add($v);
    }

    public function updateCommand($v, $unit, $id) {
        if (!$this->isMyContract($v['contract_id'], $unit))
            return false; //neoprávěný přístup
        return $this->table->update($v, $id);
    }

    public function getAllCommands($unitId) {
        return $this->table->getAll($unitId);
    }

    /**
     * vraci všechny přikazy navazane na smlouvu
     * @param type $unitId
     * @param type $contractId
     * @return type 
     */
    public function getAllCommandsByContract($unitId, $contractId) {
        return $this->table->getAllByContract($unitId, $contractId);
    }

    /**
     * vraci všechny přikazy navazane na vozidlo
     * @param type $unitId
     * @param type $vehicleId
     * @return type 
     */
    public function getAllCommandsByVehicle($unitId, $vehicleId) {
        return $this->table->getAllByVehicle($unitId, $vehicleId);
    }

    /**
     * uzavře cestovní příkaz a nastavi cas uzavření
     * @param type $commandId
     */
    public function closeCommand($commandId) {
        return $this->table->changeState($commandId, date("Y-m-d H:i:s"));
    }

    public function openCommand($commandId) {
        return $this->table->changeState($commandId, NULL);
    }

    public function deleteCommand($commandId) {
        return $this->table->delete($commandId);
    }

}