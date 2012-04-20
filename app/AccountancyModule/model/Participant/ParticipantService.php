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


    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     * @return array 
     */
    public function getAllParticipant($eventId) {
        $id = __FUNCTION__ . $eventId;
        if (!($res = $this->load($id))) {
            $res = $this->save($id, $this->skautIS->event->ParticipantGeneralAll(array("ID_EventGeneral" => $eventId)));
        }
        if(!is_array($res))//pokud je prázdná třída stdClass
            $res = array();
        return $res;
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
        $ret = ArrayHash::from(array());
        
        if (empty($participants)) {
            foreach ($all as $people) {
                $ret[$people->ID] = $people->DisplayName;
            }
        } else {
            $check = array();
            foreach ($participants as $p) {
                $check[$p->ID_Person] = true;
            }
            foreach ($all as $p) {
                if (!array_key_exists($p->ID, $check)) {
                    $ret[$p->ID] = $p->DisplayName;
                }
            }
        }
        return $ret;
    }

    /**
     * přidat účastníka k akci
     * @param int $eventId
     * @param int $participantId
     * @return type
     */
    public function add($eventId, $participantId) {
        return $this->skautIS->event->ParticipantGeneralInsert(array(
                    "ID_EventGeneral" => $eventId,
                    "ID_Person" => $participantId,
                ));
    }

    /**
     * vytvoří nového účastníka
     * @param int $eventId
     * @param int $participantId
     * @return type
     */
    public function addNew($eventId, $person) {
        return $this->skautIS->event->ParticipantGeneralInsert(array(
                    "ID_EventGeneral" => $eventId,
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
     * @param int $participantId
     * @param int $days 
     */
    public function setDays($participantId, $days) {
        $this->skautIS->event->ParticipantGeneralUpdate(array("ID" => $participantId, "Days" => $days));
    }

    /**
     * nastaví částku co účastník zaplatil
     * @param int $participantId
     * @param int $payment - částka
     */
    public function setPayment($participantId, $payment) {
        $this->skautIS->event->ParticipantGeneralUpdate(array("ID" => $participantId, self::PAYMENT => $payment));
    }

    /**
     * odebere účastníka
     * @param type $person_ID
     * @return type 
     */
    public function removeParticipant($participantId) {
        return $this->skautIS->event->ParticipantGeneralDelete(array("ID" => $participantId, "DeletePerson" => false));
    }

    /**
     * hromadné nastavení účastnické částky
     * @param int $eventId - ID ake
     * @param int $newPayment - nově nastavený poplatek
     * @param bool $rewrite - přepisovat staré údaje?
     */
    public function setPaymentMass($eventId, $newPayment, $rewrite = false) {
        if ($newPayment < 0)
            $newPayment = 0;
        $participants = $this->getAllParticipant($eventId);
        foreach ($participants as $p) {
            $paid = isset($p->{self::PAYMENT}) ? $p->{self::PAYMENT} : 0;
            if (($paid == $newPayment) || (($paid != 0 && $paid != NULL) && !$rewrite)) //není změna nebo není povolen přepis
                continue;
            $this->setPayment($p->ID, $newPayment);
        }
    }

    /**
     * celkově vybraná částka
     * @param int $eventId
     * @return int - vybraná částka 
     */
    public function getTotalPayment($eventId) {

        function paymentSum($res, $v) {
            return $res += $v->{ParticipantService::PAYMENT};
        }

        return array_reduce($this->getAllParticipant($eventId), "paymentSum");
    }

    /**
     * vrací počet osobodní na dané akci
     * @param int $eventId
     * @return int 
     */
    public function getPersonsDays($eventId) {

        function daySum($res, $v) {
            return $res += $v->Days;
        }

        return array_reduce($this->getAllParticipant($eventId), "daySum");
    }

    /**
     * počet účastníků
     * @param type $eventId
     * @return type 
     */
    public function getCount($eventId) {
        return count($this->getAllParticipant($eventId));
    }

    /**
     * přidá příjmový paragon za všechny účastníky
     * @param int $eventId
     */
    public function addPaymentsToCashbook($eventId, EventService $eventService, ChitService $chitService) {
        $func = $eventService->getFunctions($eventId);
        $total = $this->getTotalPayment($eventId);
        $date = $eventService->get($eventId)->StartDate;

        $chit = array(
            "date" => $date,
            "recipient" => isset($func[EventService::ECONOMIST]->Person) ? $func[EventService::ECONOMIST]->Person : NULL,
            "purpose" => "Účastnické poplatky",
            "price" => $total,
            "priceText" => $total,
            "type" => "pp",
        );
        try {
            $chitService->add($eventId, $chit);
        } catch (InvalidArgumentException $exc) {
            return FALSE;
        }
        return TRUE;
    }

}
