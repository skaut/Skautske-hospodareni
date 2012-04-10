<?php

/**
 * slouží pro obsluhu účastníků
 * @author Hána František
 */
class ParticipantService extends BaseService {
    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    const PAYMENT = "Note";

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

        if (is_array($participants)) {
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
     * @param int $actionId
     * @param int $participantId
     * @return type
     */
    public function addParticipant($actionId, $participantId) {
        return $this->skautIS->event->ParticipantGeneralInsert(array(
                    "ID_EventGeneral" => $actionId,
                    "ID_Person" => $participantId,
                ));
    }

    /**
     * vytvoří nového účastníka
     * @param int $actionId
     * @param int $participantId
     * @return type
     */
    public function addParticipantNew($actionId, $person) {
        return $this->skautIS->event->ParticipantGeneralInsert(array(
                    "ID_EventGeneral" => $actionId,
                    "Person" => array(
                        "FirstName" => $person['firstName'],
                        "LastName" => $person['lastName'],
                        "NickName" => $person['nick'],
                        PAYMENT => $person['note'],
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
        $this->skautIS->event->ParticipantGeneralUpdate(array("ID" => $ID, self::PAYMENT => $payment));
    }

    /**
     * odebere účastníka
     * @param type $person_ID
     * @return type 
     */
    public function removeParticipant($pid) {
        return $this->skautIS->event->ParticipantGeneralDelete(array("ID" => $pid, "DeletePerson" => false));
    }

    /**
     * hromadné nastavení účastnické částky
     * @param int $actionId - ID ake
     * @param int $newPayment - nově nastavený poplatek
     * @param bool $rewrite - přepisovat staré údaje?
     */
    public function setPaymentMass($actionId, $newPayment, $rewrite = false) {
        if ($newPayment < 0)
            $newPayment = 0;
        $par = $this->getAllParticipants($actionId);
        foreach ($par as $p) {
            $paid = isset($p->{self::PAYMENT}) ? $p->{self::PAYMENT} : 0;
            if (($paid == $newPayment) || (($paid != 0 && $paid != NULL) && !$rewrite)) //není změna nebo není povolen přepis
                continue;
            $this->setPayment($p->ID, $newPayment);
        }
    }

    /**
     * celkově vybraná částka
     * @param int $actionId
     * @return int - vybraná částka 
     */
    public function getTotalPayment($actionId) {
        $participants = $this->getAllParticipants($actionId);
        $res = 0;
        foreach ($participants as $p) {
            $res += $p->{self::PAYMENT};
        }
        return $res;
    }

    /**
     * přidá příjmový paragon za všechny účastníky
     * @param int $actionId
     */
    public function addPaymentsToCashbook($actionId) {
        $cs = new ChitService();
        $as = new ActionService();

        $func = $as->getFunctions($actionId);
        $total = $this->getTotalPayment($actionId);

        $chit = array(
            "date" => $as->get($actionId)->StartDate,
            "recipient" => isset($func[ActionService::ECONOMIST]->Person) ? $func[ActionService::ECONOMIST]->Person : NULL,
            "purpose" => "Účastnické poplatky",
            "price" => $total,
            "priceText" => $total,
            "type" => "pp",
        );
        try {
            $cs->add($actionId, $chit);
        } catch (InvalidArgumentException $exc) {
            return false;
        }
    }

}
