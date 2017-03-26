<?php

namespace Model;

use Nette\Caching\IStorage;
use Skautis\Skautis;

/**
 * slouží pro obsluhu účastníků
 * @author Hána František <sinacek@gmail.com>
 */
class   ParticipantService extends MutableBaseService
{

    /** @var ParticipantTable */
    private $table;

    public function __construct(string $name, ParticipantTable $table, Skautis $skautIS, IStorage $cacheStorage)
    {
        parent::__construct($name, $skautIS, $cacheStorage);
        $this->table = $table;
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    const PAYMENT = "Note";

    public function get($participantId)
    {
        $data = \Nette\Utils\ArrayHash::from($this->skautis->event->{"Participant" . $this->typeName . "Detail"}(["ID" => $participantId]));
        $detail = $this->table->get($participantId);
        if ($detail === FALSE) {//u akcí to v tabulce nic nenajde
            $data->payment = isset($data->{self::PAYMENT}) ? (int)$data->{self::PAYMENT} : 0;
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
    public function getAll($ID_Event)
    {
        //$this->enableDaysAutocount($ID);
        $cacheId = __FUNCTION__ . $ID_Event;
        if (!($participants = $this->loadSes($cacheId))) {
            $participants = (array)$this->skautis->event->{"Participant" . $this->typeName . "All"}(["ID_Event" . $this->typeName => $ID_Event]);
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
            return [];
        }
        return $participants;
    }

    /**
     * přidat účastníka k akci
     * @param int $ID
     * @param int $participantId
     * @throws \Skautis\Wsdl\WsdlException
     * @return bool
     */
    public function add($ID, $participantId): bool
    {
        try {
            return (bool)$this->skautis->event->{"Participant" . $this->typeName . "Insert"}([
                "ID_Event" . $this->typeName => $ID,
                "ID_Person" => $participantId,
            ]);
        } catch (\Skautis\Wsdl\WsdlException $ex) {
            if (!preg_match("/Chyba validace \(Participant_PersonIsAllreadyParticipant(General)?\)/", $ex->getMessage())) {
                throw $ex;
            }
        }

        return FALSE;
    }

    /**
     * vytvoří nového účastníka
     * @param int $ID
     * @param int $person
     */
    public function addNew($ID, $person): void
    {
        $newPaerticipantArr = $this->skautis->event->{"Participant" . $this->typeName . "Insert"}([
            "ID_Event" . $this->typeName => $ID,
            "Person" => [
                "FirstName" => $person['firstName'],
                "LastName" => $person['lastName'],
                "NickName" => $person['nick'],
                "Note" => "",
            ],
        ]);
        $this->personUpdate($newPaerticipantArr->ID_Person, $person);
    }

    /**
     * upravuje údaje zadané osoby
     * @param int $pid
     * @param array $data
     */
    public function personUpdate($pid, $data): void
    {
        $this->skautis->org->PersonUpdateBasic([
            "ID" => $pid,
            "FirstName" => isset($data['firstName']) ? $data['firstName'] : NULL,
            "LastName" => isset($data['lastName']) ? $data['lastName'] : NULL,
            "IdentificationCode" => NULL,
            "Birthday" => isset($data['Birthday']) ? $data['Birthday'] : NULL,
            "Street" => isset($data['street']) ? $data['street'] : NULL,
            "City" => isset($data['city']) ? $data['city'] : NULL,
            "Postcode" => isset($data['postcode']) ? $data['postcode'] : NULL,
        ]);
    }

    /**
     * upraví všechny nastavené hodnoty
     * @param int $participantId
     * @param array $arr pole hodnot (payment, days, [repayment], [isAccount])
     */
    public function update($participantId, array $arr): void
    {
        if ($this->typeName == "Camp") {
            if (isset($arr['days'])) {
                $sisData = [
                    'ID' => $participantId,
                    'Real' => TRUE,
                    'Days' => $arr['days'],
                ];
                $this->skautis->event->{"Participant" . $this->typeName . "Update"}($sisData, "participant" . $this->typeName);
            }
            $keys = ["actionId", "payment", "repayment", "isAccount"];
            $dataUpdate = [];
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
            $sisData = [
                'ID' => $participantId,
                'Real' => TRUE,
                'Days' => array_key_exists('days', $arr) ? $arr['days'] : NULL,
                self::PAYMENT => array_key_exists('payment', $arr) ? $arr['payment'] : NULL,
            ];
            $this->skautis->event->{"Participant" . $this->typeName . "Update"}($sisData, "participant" . $this->typeName);
        }
    }

    /**
     * odebere účastníka
     * @param int $participantId
     */
    public function removeParticipant($participantId): void
    {
        $this->table->deleteLocalDetail($participantId);
        $this->skautis->event->{"Participant" . $this->typeName . "Delete"}(["ID" => $participantId, "DeletePerson" => FALSE]);
    }

    /**
     * celkově vybraná částka
     * @param int $eventId
     * @return int - vybraná částka
     */
    public function getTotalPayment($eventId)
    {
        return array_reduce($this->getAll($eventId), function ($res, $v) {
            return isset($v->{ParticipantService::PAYMENT}) ? $res + $v->{ParticipantService::PAYMENT} : $res;
        });
    }

    public function getCampTotalPayment($campId, $category, $isAccount)
    {
        $res = 0;
        foreach ($this->getAll($campId) as $p) {
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($category == "adult" xor preg_match("/^Dospěl/", $p->Category)) ||
                ($isAccount == "Y" xor $p->isAccount == "Y")
            ) {
                continue;
            }
            $res += $p->payment;
        }
        return $res;
    }

    /**
     * vrací počet osobodní na dané akci
     * @param int|array $eventIdOrParticipants
     * @return int
     */
    public function getPersonsDays($eventIdOrParticipants)
    {
        if ($eventIdOrParticipants instanceof \Traversable || is_array($eventIdOrParticipants)) {
            $participants = $eventIdOrParticipants;
        } else {
            $participants = $this->getAll($eventIdOrParticipants);
        }
        return array_reduce($participants, create_function('$res,$v', 'return $res += $v->Days;'));
    }

    public function getEventStatistic($eventId)
    {
        $skautisData = $this->skautis->event->{"EventStatisticAllEventGeneral"}(["ID_EventGeneral" => $eventId]);
        return array_combine(array_map(create_function('$o', 'return $o->ID_ParticipantCategory;'), $skautisData), $skautisData);
    }

    public function activateEventStatistic($eventId)
    {
        return $this->skautis->event->{"EventGeneralUpdateStatisticAutoComputed"}(["ID" => $eventId, "IsStatisticAutoComputed" => TRUE], "eventGeneral");
    }

    public function getPotencialCampParticipants($eventId)
    {
        $res = [];
        foreach ($this->skautis->org->{"PersonAllEventCampMulti"}(["ID_EventCamp" => $eventId]) as $p) {
            $res[$p->ID] = $p->DisplayName;
        }
        natcasesort($res);
        return $res;
    }

    protected function setPersonName(&$person): void
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $person->Person, $matches);
        $person->LastName = $matches['last'];
        $person->FirstName = $matches['first'];
        $person->NickName = isset($matches['nick']) ? $matches['nick'] : NULL;
    }

}
