<?php

/**
 * @author Hána František
 * správa cestovních příkazů
 */
class TravelService extends BaseService {
    
    protected $tableTravel;
    protected $tableContract;

    public function __construct() {
        /** @var TravelTable */
        $this->table = new CommandTable();
        $this->tableTravel = new TravelTable();
        $this->tableContract = new ContractTable();
    }
    
    public function isMyContract($contractId, $unit){
        return $this->getContract($contractId)->unit_id == $unit->ID ? TRUE : FALSE;
    }



    public function getTravel($commandId){
        return $this->tableTravel->get($commandId);
    }
    
    public function getTravels($commandId){
        return $this->tableTravel->getAll($commandId);
    }


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
            $res[$i->id] = $i->unit_person . " <=> " . $i->driver_name ;
        }
        return $res;
    }
    
    public function addContract($values) {
        if(!$values['end'])
            $values['end'] = date("Y-m-d", strtotime("+ 3 years", $values['start']->getTimestamp()));//nastavuje platnost smlouvy na 3 roky
        return $this->tableContract->add($values);
    }

    public function getCommand($commandId) {
        return $this->table->get($commandId);
    }
    public function addCommand($v, $unit) {
        if(!$this->isMyContract($v['contract_id'], $unit))
            return false; //neoprávěný přístup
        return $this->table->add($v);
    }
    
    public function updateCommand($v, $unit, $id) {
        if(!$this->isMyContract($v['contract_id'], $unit))
            return false; //neoprávěný přístup
        return $this->table->update($v, $id);
    }
    
    public function getAllCommands($unitId, $commandId = NULL) {
        return $this->table->getAll($unitId, $commandId);
    }
   

    public function deleteCommand($commandId) {
        return $this->table->delete($commandId);
    }
    public function deleteTravel($travelId) {
        return $this->tableTravel->delete($travelId);
    }
}