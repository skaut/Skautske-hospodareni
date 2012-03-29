<?php

class ParticipantService extends BaseService {

    public function __construct() {
        parent::__construct();
        /** @var ParticipantTable */
        $this->table = new ParticipantTable();
    }

    /**
     * vrací seznam účastníků
     * @return array 
     */
    public function getAllParticipants($actionId) {
        return $this->skautIS->event->ParticipantGeneralAll(array("ID_EventGeneral" => $actionId));
    }

    /**
     * vrací seznam všech osob
     * @param ID_Unit $unitId - ID_Unit
     * @param bool $onlyDirectMember - pouze přímé členy?
     * @return array 
     */
    public function getAll($unitId = NULL, $onlyDirectMember = true, $participants = NULL) {
        $unitId = $unitId === NULL ? $this->skautIS->getUnitId() : $unitId;
        $onlyDirectMember = (bool) $onlyDirectMember;

        $all = $this->skautIS->org->PersonAll(array(
                    "ID_Unit" => $unitId,
                    "OnlyDirectMember" => $onlyDirectMember,
                ));
        
        if(is_array($participants)){
            foreach ($participants as $p) {
                $check[$p->ID_Person] = true;
            }
            $ret = ArrayHash::from(array());
            foreach ($all as $p) {
                if (!array_key_exists($p->ID, $check)) {
                    $ret[$p->ID] = $p->DisplayName;
                    //$ch = $group->addCheckbox($p->ID, $p->DisplayName);
                }
            }
        } else {
            $ret = $all;
        }
        
        return $ret;
    }

    /**
     * přidat účastníka k akci
     * @param type $actionId
     * @param type $participantId
     * @return type
     */
    public function addParticipant($actionId, $participantId) {
        return $this->skautIS->event->ParticipantGeneralInsert(array(
                    "ID_EventGeneral" => $actionId,
                    "ID_Person" => $participantId,
                ));
    }

    /**
     *
     * @param type $actionId
     * @param type $participantId
     * @return type
     */
    public function addParticipantNew($actionId, $person) {
        return $this->skautIS->event->ParticipantGeneralInsert(array(
                    "ID_EventGeneral" => $actionId,
                    "Person" => array(
                        "FirstName" => $person['firstName'],
                        "LastName" => $person['lastName'],
                        "NickName" => $person['nick'],
                        "Note" => $person['note'],
                    ),
                ));
    }

    /**
     * nastaví účastníkovi počet dní účasti
     * @param int $ID - ID účastníka
     * @param int $days 
     */
    public function setDays($ID, $days) {
        $this->skautIS->event->ParticipantGeneralUpdate(array("ID" => $ID, "Days" => $days));
    }

    /**
     * nastaví částku co účastník zaplatil
     * @param int $ID - ID účastníka
     * @param int $payment - částka
     */
    public function setPayment($ID, $payment) {
        $this->skautIS->event->ParticipantGeneralUpdate(array("ID" => $ID, "Note" => $payment));
    }

    /**
     * odebere účastníka
     * @param type $person_ID
     * @return type 
     */
    public function removeParticipant($pid) {
        return $this->skautIS->event->ParticipantGeneralDelete(array("ID" => $pid, "DeletePerson"=>false));
    }
    
    /**
     * hromadné nastavení účastnické částky
     * @param int $actionId - ID ake
     * @param int $newPayment - nově nastavený poplatek
     * @param bool $rewrite - přepisovat staré údaje?
     */
    public function setPaymentMass($actionId, $newPayment, $rewrite = false) {
        if($newPayment < 0)
            $newPayment = 0;
        $par = $this->getAllParticipants($actionId);
        foreach ($par as $p){
            $paid = $p->Note;
            if(($paid == $newPayment) || (($paid != 0 && $paid != NULL) && !$rewrite)) //není změna nebo není povolen přepis
                continue;
            $this->setPayment($p->ID, $newPayment);
        }
    }

}
