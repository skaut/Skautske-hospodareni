<?php

namespace Model;

/**
 * slouží pro obsluhu účastníků
 * @author Hána František <sinacek@gmail.com>
 */
class ParticipantService extends MutableBaseService {

    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage, $connection) {
        parent::__construct($name, $longName, $expire, $skautIS, $cacheStorage, $connection);
        /** @var ParticipantTable */
        $this->table = new ParticipantTable($connection);
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
        if (!($res = $this->loadSes($cacheId))) {
            $tmp = $this->skautIS->event->{"Participant" . self::$typeName . "All"}(array("ID_Event" . self::$typeName => $ID));
            $res = array();
            foreach ($tmp as $p) {//objekt má vzdy Note a je pod associativnim klicem
                $p->payment = isset($p->{self::PAYMENT}) ? $p->{self::PAYMENT} : 0;
                $res[$p->ID] = $p;
            }
            $this->saveSes($cacheId, $res);
        }
        if (!is_array($res)) {//pokud je prázdná třída stdClass
            return array();
        }
        return $res;
    }

    public function get($participantId) {
        $tmp = $this->skautIS->event->{"Participant" . self::$typeName . "Detail"}(array("ID" => $participantId));
        $data = $this->table->get($participantId);
        if ($data === FALSE) {//u akcí to v tabulce nic nenajde
            $data = array("payment" => (int) @$tmp->{self::PAYMENT}, 'participantId'=>$tmp->ID);
        }
        $data['days'] = (int) $tmp->Days;
        return $data;
    }

    /**
     * vrací další informace o účastníkovi
     * @param type $ID tábora
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
            if (!isset($participants[$pid]->isAccount)) {
                $participants[$pid]->isAccount = null;
            }
            if (!isset($participants[$pid]->payment)) {
                $participants[$pid]->payment = null;
            }
            if (!isset($participants[$pid]->repayment)) {
                $participants[$pid]->repayment = null;
            }
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
     * @param int $person
     * @return type
     */
    public function addNew($ID, $person) {
        $newPaerticipantArr = $this->skautIS->event->{"Participant" . self::$typeName . "Insert"}(array(
            "ID_Event" . self::$typeName => $ID,
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
        $data = array(
            "ID" => $pid,
            "FirstName" => isset($data['firstName']) ? $data['firstName'] : null,
            "LastName" => isset($data['lastName']) ? $data['lastName'] : null,
            "IdentificationCode" => null,
            "Birthday" => isset($data['Birthday']) ? $data['Birthday'] : null,
            "Street" => isset($data['street']) ? $data['street'] : null,
            "City" => isset($data['city']) ? $data['city'] : null,
            "Postcode" => isset($data['postcode']) ? $data['postcode'] : null,
        );
        $this->skautIS->org->PersonUpdate($data, "person");
    }

    /**
     * upraví všechny nastavené hodnoty
     * @param int $participantId
     * @param array $arr pole hodnot (payment, days, [repayment], [isAccount])
     */
    public function update($participantId, array $arr) {
        if (self::$typeName == "Camp") {
            if (isset($arr['days'])) {
                $sisData = array(
                    'ID' => $participantId,
                    'Real' => TRUE,
                    'Days' => $arr['days'],
                );
                $this->skautIS->event->{"Participant" . self::$typeName . "Update"}($sisData, "participant" . self::$typeName);
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
            $this->skautIS->event->{"Participant" . self::$typeName . "Update"}($sisData, "participant" . self::$typeName);
        }
    }

    /**
     * odebere účastníka
     * @param type $participantId
     * @return type 
     */
    public function removeParticipant($participantId) {
        $this->table->deleteDetail($participantId);
        return $this->skautIS->event->{"Participant" . self::$typeName . "Delete"}(array("ID" => $participantId, "DeletePerson" => false));
    }

    public function getAllPersonDetail($ID, $participants = NULL) {
        if ($participants == NULL) {
            $participants = $this->getAll($ID);
        }
        $res = array();
        foreach ($participants as $k => $par) {
            try {
//                $res[$k] = array_merge((array)$par, (array)$this->skautIS->event->{"Participant" . self::$typeName . "Detail"}(array("ID" => $par->ID)));
                $res[$k] = array_merge((array) $par, (array) $this->skautIS->org->PersonDetail(array("ID" => $par->ID_Person)));
            } catch (\SkautIS\Exception\WsdlException $exc) {
                $res[$k] = (array) $par;
            }
        }
        return \Nette\ArrayHash::from($res);
    }

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
        foreach ($this->getAllWithDetails($campId) as $p) {
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
    
    public function getEventStatistic($eventId){
        $data = array();
        foreach ($this->skautIS->event->{"EventStatisticAllEventGeneral"}(array("ID_EventGeneral" => $eventId)) as $item){
            $data[$item->ID_ParticipantCategory] = $item;
        }
        return $data;
    }
    
    public function activateEventStatistic($eventId){
        return $this->skautIS->event->{"EventGeneralUpdateStatisticAutoComputed"}(array("ID" => $eventId, "IsStatisticAutoComputed"=>TRUE), "eventGeneral");
    }
    
    public function getPotencialCampParticipants($eventId){
        $res = array();
        foreach ($this->skautIS->org->{"PersonAllEventCampMulti"}(array("ID_EventCamp" => $eventId)) as $p){
            $res[$p->ID] = $p->DisplayName;
        }
        natcasesort($res);
        return $res;
    }

}
