<?php

namespace Model;

/**
 * slouží pro obsluhu účastníků
 * @author Hána František <sinacek@gmail.com>
 */
class ParticipantService extends MutableBaseService {

    public function __construct($name, $skautIS, $cacheStorage, $connection) {
        parent::__construct($name, $skautIS, $cacheStorage, $connection);
        /** @var ParticipantTable */
        $this->table = new ParticipantTable($connection);
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    const PAYMENT = "Note";

    public function get($participantId) {
        $data = (array) $this->skautis->event->{"Participant" . $this->typeName . "Detail"}(array("ID" => $participantId));
        $detail = $this->table->get($participantId);
        if ($detail === FALSE) {//u akcí to v tabulce nic nenajde
            $data->payment = isset($data->{self::PAYMENT}) ? (int) $data->{self::PAYMENT} : 0;
        }
        $this->setPersonName($data);
        $data->days = (int) $data->Days;
        return $data;
    }

    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     * @param type $ID
     * @return array 
     */
    public function getAll($ID) {
        //$this->enableDaysAutocount($ID);
        $cacheId = __FUNCTION__ . $ID;
        if (!($participants = $this->loadSes($cacheId))) {
            $participants = $this->skautis->event->{"Participant" . $this->typeName . "All"}(array("ID_Event" . $this->typeName => $ID));
            $campDetails = $this->type == "camp" ? $this->table->getAllCampDetails($ID) : array();
            if ($this->type == "camp") {
                foreach (array_diff(array_keys($campDetails), array_map(create_function('$o', 'return $o->ID;'), $participants)) as $idForDelete) {
                    $this->table->deleteDetail($idForDelete); //delete zaznam, protoze neexistuje k nemu ucastnik
                }
            }

            foreach ($participants as $p) {//objekt má vzdy Note a je pod associativnim klicem
                $p->payment = isset($p->{self::PAYMENT}) ? $p->{self::PAYMENT} : 0;
                $p->isAccount = array_key_exists($p->ID, $campDetails) ? $campDetails[$p->ID]->isAccount : null;
                $p->repayment = array_key_exists($p->ID, $campDetails) ? $campDetails[$p->ID]->repayment : null;
                $this->setPersonName($p);
            }
            $this->saveSes($cacheId, $participants);
        }
        if (!is_array($participants)) {//pokud je prázdná třída stdClass
            return array();
        }
        return $participants;
    }

//    /**
//     * vrací další informace o účastníkovi
//     * @param type $ID tábora
//     * @return type
//     */
//    public function getAllWithDetails($ID) {
//        $participants = $this->getAll($ID);
//        $details = $this->table->getAllCampDetails($ID);
//
//        foreach ($details as $d) {
//            if (array_key_exists($d->participantId, $participants)) {
//                $participants[$d->participantId]->payment = $d->payment;
//                $participants[$d->participantId]->repayment = $d->repayment;
//                $participants[$d->participantId]->isAccount = $d->isAccount;
//            } else {
//                $this->table->deleteDetail($d->participantId); //delete zaznam, protoze neexistuje k nemu ucastnik
//            }
//        }
//
//        foreach ($participants as $pid => $p) {
//            if (!isset($participants[$pid]->isAccount)) {
//                $participants[$pid]->isAccount = null;
//            }
//            if (!isset($participants[$pid]->payment)) {
//                $participants[$pid]->payment = null;
//            }
//            if (!isset($participants[$pid]->repayment)) {
//                $participants[$pid]->repayment = null;
//            }
//        }
//        return $participants;
//    }

    /**
     * přidat účastníka k akci
     * @param int $ID
     * @param int $participantId
     * @return type
     */
    public function add($ID, $participantId) {
        return $this->skautis->event->{"Participant" . $this->typeName . "Insert"}(array(
                    "ID_Event" . $this->typeName => $ID,
                    "ID_Person" => $participantId,
        ));
    }

    /**
     * vytvoří nového účastníka
     * @param int $ID
     * @param int $person
     * @return type
     */
    public function addNew($ID, $person) {
        $newPaerticipantArr = $this->skautis->event->{"Participant" . $this->typeName . "Insert"}(array(
            "ID_Event" . $this->typeName => $ID,
            "Person" => array(
                "FirstName" => $person['firstName'],
                "LastName" => $person['lastName'],
                "NickName" => $person['nick'],
                "Note" => "",
//                        "Note" => $person['note'], //poznámka osoby, ne účastníka
            ),
        ));
        $this->personUpdate($newPaerticipantArr->ID_Person, $person);
    }

    /**
     * upravuje údaje zadané osoby
     * @param type $pid
     * @param type $data
     */
    public function personUpdate($pid, $data) {
        $this->skautis->org->PersonUpdateBasic(array(
            "ID" => $pid,
            "FirstName" => isset($data['firstName']) ? $data['firstName'] : null,
            "LastName" => isset($data['lastName']) ? $data['lastName'] : null,
            "IdentificationCode" => null,
            "Birthday" => isset($data['Birthday']) ? $data['Birthday'] : null,
            "Street" => isset($data['street']) ? $data['street'] : null,
            "City" => isset($data['city']) ? $data['city'] : null,
            "Postcode" => isset($data['postcode']) ? $data['postcode'] : null,
                ));
    }

    /**
     * upraví všechny nastavené hodnoty
     * @param int $participantId
     * @param array $arr pole hodnot (payment, days, [repayment], [isAccount])
     */
    public function update($participantId, array $arr) {
        if ($this->typeName == "Camp") {
            if (isset($arr['days'])) {
                $sisData = array(
                    'ID' => $participantId,
                    'Real' => TRUE,
                    'Days' => $arr['days'],
                );
                $this->skautis->event->{"Participant" . $this->typeName . "Update"}($sisData, "participant" . $this->typeName);
            }
            $keys = array("actionId", "payment", "repayment", "isAccount");
            $dataUpdate = array();
            $cnt = 0;
            foreach ($keys as $key) {
                if (array_key_exists($key, $arr)) {
                    $dataUpdate[$key] = $arr[$key];
                    $cnt++;
                }
            }
            if ($cnt > 1) {
                $this->table->update($participantId, $dataUpdate);
            }
        } else {
            $sisData = array(
                'ID' => $participantId,
                'Real' => TRUE,
                'Days' => $arr['days'],
                self::PAYMENT => $arr['payment'],
            );
            $this->skautis->event->{"Participant" . $this->typeName . "Update"}($sisData, "participant" . $this->typeName);
        }
    }

    /**
     * odebere účastníka
     * @param type $participantId
     * @return type 
     */
    public function removeParticipant($participantId) {
        $this->table->deleteDetail($participantId);
        return $this->skautis->event->{"Participant" . $this->typeName . "Delete"}(array("ID" => $participantId, "DeletePerson" => false));
    }

//    public function getAllPersonDetail($ID) {
//        $participants = $this->getAll($ID);
//        foreach ($participants as $k => $par) {
//            try {
//                $participants[$k] = array_merge((array) $par + $this->get($par->ID));
//            } catch (\SkautIS\Exception\WsdlException $exc) {
//                $participants[$k] = (array) $par;
//            }
//        }
//        return ArrayHash::from($participants);
//    }

    /**
     * celkově vybraná částka
     * @param int $eventId
     * @return int - vybraná částka 
     */
    public function getTotalPayment($eventId) {
        return array_reduce($this->getAll($eventId), function ($res, $v) {
            return isset($v->{ParticipantService::PAYMENT}) ? $res + $v->{ParticipantService::PAYMENT} : $res;
        });
    }

    public function getCampTotalPayment($campId, $category, $isAccount) {
        $res = 0;
        foreach ($this->getAll($campId) as $p) {
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($category == "adult" xor preg_match("/^Dospěl/", $p->Category)) ||
                    ($isAccount == "Y" xor $p->isAccount == "Y")) {
                continue;
            }
            $res += ($p->payment - $p->repayment);
        }
        return $res;
    }

    /**
     * vrací počet osobodní na dané akci
     * @param int $eventId
     * @return int 
     */
    public function getPersonsDays($eventId) {
        return array_reduce($this->getAll($eventId), function ($res, $v) {
            return $res += $v->Days;
        });
    }

    public function getEventStatistic($eventId) {
        $skautisData = $this->skautis->event->{"EventStatisticAllEventGeneral"}(array("ID_EventGeneral" => $eventId));
        return array_combine(array_map(create_function('$o', 'return $o->ID_ParticipantCategory;'), $skautisData), $skautisData);
    }

    public function activateEventStatistic($eventId) {
        return $this->skautis->event->{"EventGeneralUpdateStatisticAutoComputed"}(array("ID" => $eventId, "IsStatisticAutoComputed" => TRUE), "eventGeneral");
    }

    public function getPotencialCampParticipants($eventId) {
        $res = array();
        foreach ($this->skautis->org->{"PersonAllEventCampMulti"}(array("ID_EventCamp" => $eventId)) as $p) {
            $res[$p->ID] = $p->DisplayName;
        }
        natcasesort($res);
        return $res;
    }

    protected function setPersonName(&$person) {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $person->Person, $matches);
        $person->LastName = $matches['last'];
        $person->FirstName = $matches['first'];
        $person->NickName = isset($matches['nick']) ? $matches['nick'] : null;
    }

}
