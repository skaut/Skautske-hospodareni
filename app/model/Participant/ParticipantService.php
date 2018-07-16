<?php

declare(strict_types=1);

namespace Model;

use Model\Services\Language;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use function array_column;
use function array_combine;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_reduce;
use function array_sum;
use function is_array;
use function natcasesort;
use function preg_match;
use function stripos;
use function usort;

class ParticipantService extends MutableBaseService
{
    public const PRAGUE_SUPPORTABLE_AGE = 18;
    public const PRAGUE_UNIT_PREFIX     = 11;

    /** @var ParticipantTable */
    private $table;

    public function __construct(string $name, ParticipantTable $table, Skautis $skautIS)
    {
        parent::__construct($name, $skautIS);
        $this->table = $table;
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    public const PAYMENT = 'Note';

    public function get($participantId) : array
    {
        $data   = ArrayHash::from($this->skautis->event->{'Participant' . $this->typeName . 'Detail'}(['ID' => $participantId]));
        $detail = $this->table->get($participantId);
        if ($detail === false) {//u akcí to v tabulce nic nenajde
            $data->payment = isset($data->{self::PAYMENT}) ? (int) $data->{self::PAYMENT} : 0;
        }
        $this->setPersonName($data);

        return (array) $data;
    }

    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     *
     * @return array
     */
    public function getAll(int $ID_Event) : array
    {
        $cacheId = __FUNCTION__ . $ID_Event;
        if (! ($participants = $this->loadSes($cacheId))) {
            $participants = (array) $this->skautis->event->{'Participant' . $this->typeName . 'All'}(['ID_Event' . $this->typeName => $ID_Event]);
            if ($this->type === 'camp') {
                $campLocalDetails = $this->table->getCampLocalDetails($ID_Event);
                foreach (array_diff(array_keys($campLocalDetails), array_column($participants, 'ID')) as $idForDelete) {
                    $this->table->deleteLocalDetail($idForDelete); //delete zaznam, protoze neexistuje k nemu ucastnik
                }
            }

            foreach ($participants as $p) {//objekt má vzdy Note a je pod associativnim klicem
                if (isset($campLocalDetails) && array_key_exists($p->ID, $campLocalDetails)) {
                    $p->payment   = $campLocalDetails[$p->ID]->payment;
                    $p->isAccount = $campLocalDetails[$p->ID]->isAccount;
                    $p->repayment = $campLocalDetails[$p->ID]->repayment;
                } else {
                    $p->payment   = ($p->{self::PAYMENT} ?? 0);
                    $p->isAccount = null;
                    $p->repayment = null;
                }
                $this->setPersonName($p);
            }

            $this->saveSes($cacheId, $participants);
        }
        if (! is_array($participants)) {//pokud je prázdná třída stdClass
            return [];
        }

        usort(
            $participants,
            function ($one, $two) : int {
                return Language::compare($one->Person, $two->Person);
            }
        );

        return $participants;
    }

    /**
     * přidat účastníka k akci
     *
     * @throws WsdlException
     */
    public function add(int $ID, int $participantId) : bool
    {
        try {
            return (bool) $this->skautis->event->{'Participant' . $this->typeName . 'Insert'}(
                [
                'ID_Event' . $this->typeName => $ID,
                'ID_Person' => $participantId,
                ]
            );
        } catch (WsdlException $ex) {
            if (! preg_match('/Chyba validace \(Participant_PersonIsAllreadyParticipant(General)?\)/', $ex->getMessage())) {
                throw $ex;
            }
        }

        return false;
    }

    /**
     * vytvoří nového účastníka
     *
     * @param array $person
     */
    public function addNew(int $ID, array $person) : void
    {
        $newParticipantArr = $this->skautis->event->{'Participant' . $this->typeName . 'Insert'}(
            [
            'ID_Event' . $this->typeName => $ID,
            'Person' => [
                'FirstName' => $person['firstName'],
                'LastName' => $person['lastName'],
                'NickName' => $person['nick'],
                'Note' => '',
            ],
            ]
        );
        $this->personUpdate($newParticipantArr->ID_Person, $person);
    }

    /**
     * upravuje údaje zadané osoby
     *
     * @param array $data
     */
    public function personUpdate(int $pid, array $data) : void
    {
        $this->skautis->org->PersonUpdateBasic(
            [
            'ID' => $pid,
            'FirstName' => $data['firstName'] ?? null,
            'LastName' => $data['lastName'] ?? null,
            'IdentificationCode' => null,
            'Birthday' => $data['Birthday'] ?? null,
            'Street' => $data['street'] ?? null,
            'City' => $data['city'] ?? null,
            'Postcode' => $data['postcode'] ?? null,
            ]
        );
    }

    /**
     * upraví všechny nastavené hodnoty
     *
     * @param array $arr pole hodnot (payment, days, [repayment], [isAccount])
     */
    public function update(int $participantId, array $arr) : void
    {
        if ($this->typeName === 'Camp') {
            if (isset($arr['days'])) {
                $sisData = [
                    'ID' => $participantId,
                    'Real' => true,
                    'Days' => $arr['days'],
                ];
                $this->skautis->event->{'Participant' . $this->typeName . 'Update'}($sisData, 'participant' . $this->typeName);
            }
            $keys       = ['actionId', 'payment', 'repayment', 'isAccount'];
            $dataUpdate = [];
            $cnt        = 0;
            foreach ($keys as $key) {
                if (! array_key_exists($key, $arr)) {
                    continue;
                }

                $dataUpdate[$key] = $arr[$key];
                $cnt++;
            }
            if ($cnt > 1) {
                $this->table->update($participantId, $dataUpdate);
            }
        } else {
            $sisData = [
                'ID' => $participantId,
                'Real' => true,
                'Days' => array_key_exists('days', $arr) ? $arr['days'] : null,
                self::PAYMENT => array_key_exists('payment', $arr) ? $arr['payment'] : null,
            ];
            $this->skautis->event->{'Participant' . $this->typeName . 'Update'}($sisData, 'participant' . $this->typeName);
        }
    }

    /**
     * odebere účastníka
     */
    public function removeParticipant(int $participantId) : void
    {
        $this->table->deleteLocalDetail($participantId);
        $this->skautis->event->{'Participant' . $this->typeName . 'Delete'}(['ID' => $participantId, 'DeletePerson' => false]);
    }

    /**
     * celkově vybraná částka
     */
    public function getTotalPayment(int $eventId) : float
    {
        return (float) array_reduce(
            $this->getAll($eventId),
            function ($res, $v) {
                return isset($v->{ParticipantService::PAYMENT}) ? $res + $v->{ParticipantService::PAYMENT} : $res;
            }
        );
    }

    public function getCampTotalPayment($campId, $category, $isAccount) : float
    {
        $res = 0;
        foreach ($this->getAll($campId) as $p) {
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($category === 'adult' xor preg_match('/^Dospěl/', $p->Category))
                || ($isAccount === 'Y' xor $p->isAccount === 'Y')
            ) {
                continue;
            }
            $res += $p->payment;
        }
        return $res;
    }

    /**
     * vrací počet osobodní na dané akci
     *
     * @param int|array $eventIdOrParticipants
     */
    public function getPersonsDays($eventIdOrParticipants) : int
    {
        if (is_array($eventIdOrParticipants)) {
            $participants = $eventIdOrParticipants;
        } else {
            $participants = $this->getAll($eventIdOrParticipants);
        }

        return array_sum(
            array_column($participants, 'Days')
        );
    }

    public function getEventStatistic($eventId)
    {
        $skautisData = $this->skautis->event->{'EventStatisticAllEventGeneral'}(['ID_EventGeneral' => $eventId]);

        return array_combine(
            array_column($skautisData, 'ID_ParticipantCategory'),
            $skautisData
        );
    }

    public function getPotencialCampParticipants($eventId)
    {
        $res = [];
        foreach ($this->skautis->org->{'PersonAllEventCampMulti'}(['ID_EventCamp' => $eventId]) as $p) {
            $res[$p->ID] = $p->DisplayName;
        }
        natcasesort($res);
        return $res;
    }

    protected function setPersonName(&$person) : void
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $person->Person, $matches);
        $person->LastName  = $matches['last'];
        $person->FirstName = $matches['first'];
        $person->NickName  = $matches['nick'] ?? null;
    }

    public function countPragueParticipants($event) : ?array
    {
        if (! Strings::startsWith($event->RegistrationNumber, self::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        $eventStartDate = new \DateTime($event->StartDate);
        $participants   = $this->getAll($event->ID);
        $underAge       = 0;
        $cityMatch      = 0;
        foreach ($participants as $p) {
            if (stripos($p->City, 'Praha') === false) {
                continue;
            }
            $cityMatch += 1;
            if ($eventStartDate->diff(new \DateTime($p->Birthday))->format('%Y') >= self::PRAGUE_SUPPORTABLE_AGE) {
                continue;
            }

            $underAge += 1;
        }
        return ['underAge' => $underAge, 'all' => $cityMatch, 'ageThreshold' => self::PRAGUE_SUPPORTABLE_AGE];
    }
}
