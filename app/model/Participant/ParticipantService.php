<?php

namespace Model;
use Dibi\Connection;
use Nette\Caching\IStorage;
use Skautis\Skautis;

/**
 * slouží pro obsluhu účastníků
 * @author Hána František <sinacek@gmail.com>
 */
class ParticipantService extends MutableBaseService
{

    /** @var ParticipantTable */
    private $table;

    public function __construct(string $name, ParticipantTable $table, Skautis $skautIS, IStorage $cacheStorage, Connection $connection)
    {
        parent::__construct($name, $skautIS, $cacheStorage, $connection);
        $this->table = $table;
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    const PAYMENT = "Note";

    public function get($participantId) {
        $data = \Nette\Utils\ArrayHash::from($this->skautis->event->{"Participant" . $this->typeName . "Detail"}(array("ID" => $participantId)));
        $detail = $this->table->get($participantId);
        if ($detail === FALSE) {//u akcí to v tabulce nic nenajde
            $data->payment = isset($data->{self::PAYMENT}) ? (int) $data->{self::PAYMENT} : 0;
        }
        $this->setPersonName($data);
        //$data->days = $data->Days;
        return $data;
    }

    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     * @param int $ID_Event
     * @return array 
     */
    public function getAll($ID_Event) {
        //$this->enableDaysAutocount($ID);
        $cacheId = __FUNCTION__ . $ID_Event;
        if (!($participants = $this->loadSes($cacheId))) {
            $participants = (array) $this->skautis->event->{"Participant" . $this->typeName . "All"}(array("ID_Event" . $this->typeName => $ID_Event));
            if ($this->type == "camp") {
                $campLocalDetails = $this->table->getCampLocalDetails($ID_Event);
                foreach (array_diff(array_keys($campLocalDetails), array_map(create_function('$o', 'return $o->ID;'), $participants)) as $idForDelete) {
                    $this->table->deleteLocalDetail($idForDelete); //delete zaznam, protoze neexistuje k nemu ucastnik
                }
            }

            foreach ($participants as $p) {//objekt má vzdy Note a je pod associativnim klicem
                if (isset($campLocalDetails) && array_key_exists($p->ID, $campLocalDetails)) {
                    $p->payment = $campLocalDetails[$p->ID]->payment;
                    $p->isAccount = $campLocalDetails[$p->ID]->isAccount;
                    $p->repayment = $campLocalDetails[$p->ID]->repayment;
                } else {
                    $p->payment = (isset($p->{self::PAYMENT}) ? $p->{self::PAYMENT} : 0);
                    $p->isAccount = NULL;
                    $p->repayment = NULL;
                }
                $this->setPersonName($p);
            }

            $this->saveSes($cacheId, $participants);
        }
        if (!is_array($participants)) {//pokud je prázdná třída stdClass
            return array();
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
        try {
            return $this->skautis->event->{"Participant" . $this->typeName . "Insert"}(array(
                        "ID_Event" . $this->typeName => $ID,
                        "ID_Person" => $participantId,
            ));
        } catch (\Skautis\Wsdl\WsdlException $ex) {
            if (!preg_match("/Chyba validace \(Participant_PersonIsAllreadyParticipant(General)?\)/", $ex->getMessage())) {
                throw $ex;
            }
            return FALSE;
        }
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
                'Days' => array_key_exists('days', $arr) ? $arr['days'] : NULL,
                self::PAYMENT => array_key_exists('payment', $arr) ? $arr['payment'] : NULL,
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
        $this->table->deleteLocalDetail($participantId);
        return $this->skautis->event->{"Participant" . $this->typeName . "Delete"}(array("ID" => $participantId, "DeletePerson" => false));
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
        foreach ($this->getAll($campId) as $p) {
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($category == "adult" xor preg_match("/^Dospěl/", $p->Category)) ||
                    ($isAccount == "Y" xor $p->isAccount == "Y")) {
                continue;
            }
            $res += $p->payment; // - $p->repayment
        }
        return $res;
    }

    /**
     * vrací počet osobodní na dané akci
     * @param int|array $eventIdOrParticipants
     * @return int 
     */
    public function getPersonsDays($eventIdOrParticipants) {
        if ($eventIdOrParticipants instanceof \Traversable || is_array($eventIdOrParticipants)) {
            $participants = $eventIdOrParticipants;
        } else {
            $participants = $this->getAll($eventIdOrParticipants);
        }
        return array_reduce($participants, create_function('$res,$v', 'return $res += $v->Days;'));
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
