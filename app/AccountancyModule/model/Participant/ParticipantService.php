<?php
class ParticipantService extends BaseService {
    public function __construct() {
        parent::__construct();
        $this->table = new ParticipantTable();
    }
    
    /**
     * vrací seznam účastníků
     * @return type 
     */
    public function getAllParticipants($actionId){
        return $this->skautIS->event->ParticipantGeneralAll(array("ID_EventGeneral" => $actionId));
    }
    
    /**
     * vrací 
     * @return type 
     */
    public function getAll(){
        return $this->skautIS->org->PersonAll(array("ID_Unit"=>$this->skautIS->getUnitId()));
    }
    
    public function addParticipant($actionId, $participantId){
        return $this->skautIS->event->ParticipantGeneralInsert(array(
            "ID_EventGeneral" => $actionId,
            "ID_Person"=>$participantId,
            ));
    }
    
    public function removeParticipant($pid){
        return $this->skautIS->event->ParticipantGeneralDelete(array("ID"=>$pid));
    }
}