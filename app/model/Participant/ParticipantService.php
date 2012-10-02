<?php

/**
 * slouží pro obsluhu účastníků
 * @author Hána František
 */
class ParticipantService extends MutableBaseService {

    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage) {
        parent::__construct($name, $longName, $expire, $skautIS, $cacheStorage);
        /** @var ParticipantTable */
        $this->table = new ParticipantTable();
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */

    const PAYMENT = "Note";

    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     * @param type $ID
     * @param bool $cache
     * @return array 
     */
    public function getAll($ID, $cache = TRUE) {
        //$this->enableDaysAutocount($ID);
        $cacheId = __FUNCTION__ . $ID;
        if (!$cache || !($res = $this->load($cacheId))) {
            $tmp = $this->skautIS->event->{"Participant" . self::$typeName . "All"}(array("ID_Event" . self::$typeName => $ID));
            $res = array();
            foreach ($tmp as $p) {//objekt má vzdy Note a je pod associativnium klicem
                $p->Note = isset($p->Note) ? $p->Note : 0;
                $res[$p->ID] = $p;
            }
            $this->save($cacheId, $res);
        }
        if (!is_array($res))//pokud je prázdná třída stdClass
            return array();
        return $res;
    }

    public function get($participantId) {
        $tmp = $this->skautIS->event->{"Participant" . self::$typeName . "Detail"}(array("ID" => $participantId));
        $data = $this->table->get($participantId);
        $data['days'] = (int) $tmp->Days;
        return $data;
    }

    /**
     * vrací další informace o účastníkovi
     * @param type $ID
     * @return type
     */
    public function getAllWithDetails($ID) {
        $participants = $this->getAll($ID);
        $details = $this->table->getAll($ID);

        foreach ($details as $d) {
            if (array_key_exists($d->participantId, $participants)) {
                $participants[$d->participantId]->payment = $d->payment;
                $participants[$d->participantId]->repayment = $d->repayment;
                $participants[$d->participantId]->isAccount = $d->isAccount;
            } else {
                $this->table->deleteDetail($d->participantId); //delete zaznam, protoze neexistuje k nemu ucastnik
            }
        }

        foreach ($participants as $pid => $p) {
            if (!isset($participants[$pid]->isAccount))
                $participants[$pid]->isAccount = null;
            if (!isset($participants[$pid]->payment))
                $participants[$pid]->payment = null;
            if (!isset($participants[$pid]->repayment))
                $participants[$pid]->repayment = null;
        }

        return $participants;
    }

    /**
     * přidat účastníka k akci
     * @param int $ID
     * @param int $participantId
     * @return type
     */
    public function add($ID, $participantId) {
        return $this->skautIS->event->{"Participant" . self::$typeName . "Insert"}(array(
                    "ID_Event" . self::$typeName => $ID,
                    "ID_Person" => $participantId,
                ));
    }

    /**
     * vytvoří nového účastníka
     * @param int $ID
     * @param int $participantId
     * @return type
     */
    public function addNew($ID, $person) {
        return $this->skautIS->event->{"Participant" . self::$typeName . "Insert"}(array(
                    "ID_Event" . self::$typeName => $ID,
                    "Person" => array(
                        "FirstName" => $person['firstName'],
                        "LastName" => $person['lastName'],
                        "NickName" => $person['nick'],
                        "Note" => $person['note'], //poznámka osoby, ne účastníka
                    ),
                ));
    }

//    /**
//     * nastaví účastníkovi počet dní účasti
//     * @param int $participantId
//     * @param int $days 
//     */
//    public function setDays($participantId, $days) {
//        $this->update($participantId, array("Days" => $days));
//    }
//
//    /**
//     * nastaví částku co účastník zaplatil
//     * @param int $participantId
//     * @param int $payment - částka
//     */
//    public function setPayment($participantId, $payment) {
//        $this->update($participantId, array(self::PAYMENT => $payment));
//    }

    /**
     * upraví všechny nastavené hodnoty
     * @param int $participantId
     * @param array $arr pole hodnot (payment, days, [repayment], [isAccount])
     */
    public function update($participantId, array $arr) {
        if (self::$typeName == "Camp") {
            $sis = array(
                'ID' => $participantId,
                'Real' => TRUE,
                'Days' => $arr['days'],
            );
            $this->skautIS->event->{"Participant" . self::$typeName . "Update"}($sis, "participant" . self::$typeName);
            $data = array(
                "actionId" => $arr['actionId'],
                "payment" => $arr['payment'],
                "repayment" => $arr['repayment'],
                "isAccount" => $arr['isAccount'],
            );
            $this->table->update($participantId, $data);
        } else {
            $sis = array(
                'ID' => $participantId,
                'Real' => TRUE,
                'Days' => $arr['days'],
                self::PAYMENT => $arr['payment'],
            );

            $this->skautIS->event->{"Participant" . self::$typeName . "Update"}($sis, "participant" . self::$typeName);
        }
    }

    /**
     * odebere účastníka
     * @param type $person_ID
     * @return type 
     */
    public function removeParticipant($participantId) {
        $this->table->deleteDetail($participantId);
        return $this->skautIS->event->{"Participant" . self::$typeName . "Delete"}(array("ID" => $participantId, "DeletePerson" => false));
    }

    public function getAllDetail($ID, $participants = NULL) {
        if ($participants == NULL) {
            $participants = $this->getAll($ID);
        }
        $res = array();
        foreach ($participants as $par) {
            $res[] = $this->skautIS->org->PersonDetail(array("ID" => $par->ID_Person));
        }
        return $res;
    }

//    /**
//     * hromadné nastavení účastnické částky
//     * @param int $eventId - ID ake
//     * @param int $newPayment - nově nastavený poplatek
//     * @param bool $rewrite - přepisovat staré údaje?
//     */
//    public function setPaymentMass($eventId, $newPayment, $rewrite = false) {
//        if ($newPayment < 0)
//            $newPayment = 0;
//        $participants = $this->getAll($eventId);
//        foreach ($participants as $p) {
//            $paid = isset($p->{self::PAYMENT}) ? $p->{self::PAYMENT} : 0;
//            if (($paid == $newPayment) || (($paid != 0 && $paid != NULL) && !$rewrite)) //není změna nebo není povolen přepis
//                continue;
//            $this->setPayment($p->ID, $newPayment);
//        }
//    }

    /**
     * celkově vybraná částka
     * @param int $eventId
     * @return int - vybraná částka 
     */
    public function getTotalPayment($eventId) {

        function paymentSum($res, $v) {
            if (isset($v->{ParticipantService::PAYMENT}))
                return $res += $v->{ParticipantService::PAYMENT};
            return 0;
        }

        return array_reduce($this->getAll($eventId), "paymentSum");
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

        return array_reduce($this->getAll($eventId), "daySum");
    }

    /**
     * počet účastníků
     * @param type $eventId
     * @return type 
     */
    public function getCount($eventId) {
        return count($this->getAll($eventId));
    }

//    /**
//     * přidá příjmový paragon za všechny účastníky
//     * @param int $eventId
//     */
//    public function addPaymentsToCashbook($eventId, EventService $eventService, ChitService $chitService) {
//        $func = $eventService->event->getFunctions($eventId);
//        $total = $this->getTotalPayment($eventId);
//        $date = $eventService->event->get($eventId)->StartDate;
//
//        $chit = array(
//            "date" => $date,
//            "recipient" => isset($func[EventService::ECONOMIST]->Person) ? $func[EventService::ECONOMIST]->Person : NULL,
//            "purpose" => "Účastnické poplatky",
//            "price" => $total,
//            "priceText" => $total,
//            "category"=> "pp",
//        );
//        try {
//            $chitService->add($eventId, $chit);
//        } catch (InvalidArgumentException $exc) {
//            return FALSE;
//        }
//        return TRUE;
//    }
}
