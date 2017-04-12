<?php

namespace Model;

use Model\DTO\Payment as DTO;
use Model\Payment\Group;
use Model\Payment\GroupNotFoundException;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\Summary;
use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentService
{

    /** @var PaymentTable */
    private $table;

    /** @var Skautis */
    private $skautis;

    /** @var IGroupRepository */
    private $groups;

    /** @var IPaymentRepository */
    private $payments;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    public function __construct(
        PaymentTable $table,
        Skautis $skautis,
        IGroupRepository $groups,
        IPaymentRepository $payments,
        IBankAccountRepository $bankAccounts
    )
    {
        $this->table = $table;
        $this->skautis = $skautis;
        $this->groups = $groups;
        $this->payments = $payments;
        $this->bankAccounts = $bankAccounts;
    }

    public function findPayment(int $id): ?DTO\Payment
    {
        try {
            return DTO\PaymentFactory::create($this->payments->find($id));
        } catch(PaymentNotFoundException $e) {
            return NULL;
        }
    }

    /**
	 * @param int $groupId
	 * @return DTO\Payment[]
	 */
    public function findByGroup(int $groupId): array
    {
        $payments = $this->payments->findByGroup($groupId);

        return array_map(function(Payment $payment) {
               return DTO\PaymentFactory::create($payment);
        }, $payments);
    }

    public function createPayment(int $groupId, string $name, ?string $email, float $amount, \DateTimeImmutable $dueDate, ?int $personId, ?int $vs, ?int $ks, string $note): void
    {
        $group = $this->groups->find($groupId);

        $payment = new Payment($group, $name, $email, $amount, $dueDate, $vs, $ks, $personId, $note);

        $this->payments->save($payment);
    }

    /**
     * @param int $pid
     * @param array $arr
     * @return bool
     */
    public function update($pid, $arr): bool
    {
        return $this->table->update($pid, $arr);
    }

    public function cancelPayment(int $pid): void
    {
        $payment = $this->payments->find($pid);
        $payment->cancel(new \DateTimeImmutable());

        $this->payments->save($payment);
    }

    public function completePayment(int $id): void
    {
        $payment = $this->payments->find($id);
        $payment->complete(new \DateTimeImmutable());

        $this->payments->save($payment);
    }

    /**
     * seznam stavů, které jsou nedokončené
     * @return array
     */
    public function getNonFinalStates()
    {
        return $this->table->getNonFinalStates();
    }

    /**
     * číslo účtu jednotky ze skautisu
     * @param int $unitId
     * @return string|NULL
     */
    public function getBankAccount(int $unitId): ?string
    {
        $accounts = $this->bankAccounts->findByUnit($unitId);

        if (empty($accounts)) {
            return NULL;
        }

        return $accounts[0]->getNumber();
    }

    /**
     * GROUP
     */

    /**
     * @param int[] $unitIds
     * @param bool $onlyOpen
     * @return DTO\Group[]
     */
    public function getGroups(array $unitIds, bool $onlyOpen): array
    {
        $groups = $this->groups->findByUnits($unitIds, $onlyOpen);

        return array_map(function (Group $group) {
            return DTO\GroupFactory::create($group);
        }, $groups);
    }

    /**
     * @param int[] $groupIds
     * @return Summary[][]
     */
    public function getGroupSummaries(array $groupIds): array
    {
        return $this->payments->summarizeGroups($groupIds);
    }

    public function createGroup(
        int $unitId,
        ?string $oType,
        ?int $sisId,
        string $label,
        ?\DateTime $maturity,
        ?int $ks,
        ?int $nextVS,
        ?float $amount,
        string $email_info,
        ?int $smtpId
    ): int
    {
        $group = new Group(
            $oType,
            $unitId,
            $sisId,
            $label,
            $amount ? $amount : NULL,
            $maturity ? \DateTimeImmutable::createFromMutable($maturity) : NULL,
            $ks,
            $nextVS,
            new \DateTimeImmutable(),
            $email_info,
            $smtpId);

        $this->groups->save($group);
        return $group->getId();
    }

    public function updateGroup(
        int $id,
        string $name,
        ?float $defaultAmount,
        ?\DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        ?int $nextVariableSymbol,
        string $emailTemplate,
        ?int $smtpId): void
    {
        $group = $this->groups->find($id);

        $group->update($name, $defaultAmount, $dueDate, $constantSymbol, $nextVariableSymbol, $emailTemplate, $smtpId);

        $this->groups->save($group);
    }

    public function getGroup($id): ?DTO\Group
    {
        try {
            $group = $this->groups->find($id);
            return DTO\GroupFactory::create($group);
        } catch (GroupNotFoundException $e) {
        }
        return NULL;
    }

    public function openGroup(int $id, string $note): void
    {
        $group = $this->groups->find($id);
        $group->open($note);
        $this->groups->save($group);
    }

    public function closeGroup(int $id, string $note): void
    {
        $group = $this->groups->find($id);
        $group->close($note);
        $this->groups->save($group);
    }

    /**
     * vrací nejvyšší hodnotu VS uvedenou ve skupině pro nezrušené platby
     * @param int $groupId
     * @return int
     */
    public function getNextVS($groupId)
    {
        return $this->table->getNextVS($groupId);
    }

    /**
     * seznam osob z dané jednotky
     * @param int $unitId
     * @param int $groupId - skupina plateb, podle které se filtrují osoby, které již mají platbu zadanou
     * @return array[] array($personId => array(...))
     */
    public function getPersons($unitId, $groupId = NULL)
    {
        $result = [];
        $persons = $this->skautis->org->PersonAll(["ID_Unit" => $unitId, "OnlyDirectMember" => TRUE]);
        if ($groupId !== NULL) {
            $payments_personIds = $this->table->getActivePaymentIds($groupId);
            if (is_array($persons)) {
                $persons = array_filter($persons, function ($v) use ($payments_personIds) {
                    return !in_array($v->ID, $payments_personIds);
                });
            }
        }

        if (is_array($persons)) {
            foreach ($persons as $p) {
                $result[$p->ID] = (array)$p;
                $result[$p->ID]['emails'] = $this->getPersonEmails($p->ID);
            }
        }
        return $result;
    }

    /**
     * vrací seznam emailů osoby
     * @param int $personId
     * @return string[]
     */
    public function getPersonEmails($personId)
    {
        $result = [];
        try {
            $emails = $this->skautis->org->PersonContactAll(['ID_Person' => $personId]);
            if (is_array($emails)) {
                usort($emails, function ($a, $b) {
                    return $a->IsMain == $b->IsMain ? 0 : ($a->IsMain > $b->IsMain) ? -1 : 1;
                });
                foreach ($emails as $c) {
                    if (mb_substr($c->ID_ContactType, 0, 5) == "email") {
                        $result[$c->Value] = $c->Value . " (" . $c->ContactType . ")";
                    }
                }
            }
        } catch (\Skautis\Wsdl\PermissionException $exc) {//odchycení bývalých členů, ke kterým už nemáme oprávnění
        }
        return $result;
    }

    /**
     * REGISTRATION
     */

    /**
     * Returns newest registration without created group
     */
    public function getNewestRegistration(): array
    {
        $unitId = $this->skautis->getUser()->getUnitId();

        $data = $this->skautis->org->UnitRegistrationAll(["ID_Unit" => $unitId, ""]);

        if ($data != new \stdClass()) { // Skautis returns empty object when no registration is found
            $registration = $data[0];
            if (!$this->table->getGroupsBySisId('registration', $registration->ID)) {
                return (array)$registration;
            }
        }

        return [];
    }

    /**
     * seznam osob z registrace
     * @param int[] $units
     * @param int $groupId ID platebni skupiny, podle ktere se filtruji osoby bez platby
     * @return array(array())
     */
    public function getPersonsFromRegistrationWithoutPayment(array $units, int $groupId)
    {
        $result = [];

        $group = $this->getGroup($groupId);

        if ($group === NULL || !in_array($group->getUnitId(), $units, TRUE)) {
            throw new \InvalidArgumentException("Nebyla nalezena platební skupina");
        }
        $persons = $this->getPersonFromRegistration($group->getSkautisId(), TRUE);

        if (is_array($persons)) {
            usort($persons, function ($a, $b) {
                return strcmp($a->Person, $b->Person);
            });

            $payments_personIds = $this->table->getActivePaymentIds($groupId);
            $persons = array_filter($persons, function ($v) use ($payments_personIds) {
                return !in_array($v->ID_Person, $payments_personIds);
            });

            foreach ($persons as $p) {
                $result[$p->ID_Person] = (array)$p;
                $result[$p->ID_Person]['emails'] = $this->getPersonEmails($p->ID_Person);
            }
        }
        return $result;
    }

    public function getPersonFromRegistration($registrationId, $includeChild = TRUE)
    {
        return ($this->skautis->org->PersonRegistrationAll([
            'ID_UnitRegistration' => $registrationId,
            'IncludeChild' => $includeChild,
        ]));
    }

    /**
     * JOURNAL
     */

    /**
     * @param int $unitId
     * @param int $year
     * @return array | NULL - format array("add" => [], "remove" => [])
     */
    public function getJournalChangesAfterRegistration($unitId, $year)
    {
        $registrations = $this->skautis->org->UnitRegistrationAll(["ID_Unit" => $unitId, "Year" => $year]);
        if (!is_array($registrations) || count($registrations) < 1) {
            return NULL;
        }
        $registrationId = reset($registrations)->ID;
        $registration = $this->getPersonFromRegistration($registrationId, FALSE);

        $regCategories = [];
        foreach ($this->skautis->org->RegistrationCategoryAll(["ID_UnitRegistration" => $registrationId]) as $rc) {
            $regCategories[$rc->ID] = $rc->IsJournal;
        }
        $unitJournals = $this->skautis->Journal->PersonJournalAllUnit(["ID_Unit" => $unitId, "ShowHistory" => FALSE, "IncludeChild" => TRUE]);

        //seznam osob s casopisem
        $personIdsWithJournal = [];
        foreach ($unitJournals as $journal) {
            $personIdsWithJournal[$journal->ID_Person] = TRUE;
        }

        $changes = ["add" => [], "remove" => []];
        foreach ($registration as $p) {
            $isRegustredWithJournal = $regCategories[$p->ID_RegistrationCategory];
            $hasPersonJournal = array_key_exists($p->ID_Person, $personIdsWithJournal);
            if ($hasPersonJournal && !$isRegustredWithJournal) {
                $changes["remove"][] = $p->Person;
            } elseif (!$hasPersonJournal && $isRegustredWithJournal) {
                $changes["add"][] = $p->Person;
            }
        }
        return ($changes);
    }

    /**
     * CAMP
     */
    public function getCamp($campId)
    {
        return $this->skautis->event->{"EventCampDetail"}(["ID" => $campId]);
    }

    public function getGroupByCampId($campId)
    {
        $g = $this->table->getGroupsBySisId('camp', $campId);
        return empty($g) ? FALSE : $g[0];
    }

    /**
     * vrací seznam id táborů se založenou aktivní skupinou
     */
    public function getCampIds()
    {
        return $this->table->getCampIds();
    }

    /* Repayments */

    public function getFioRepaymentString($repayments, $accountFrom, $date = NULL)
    {
        if ($date === NULL) {
            $date = date("Y-m-d");
        }
        $accountFromArr = explode("/", $accountFrom, 2);

        $ret = '<?xml version="1.0" encoding="UTF-8"?><Import xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.fio.cz/schema/importIB.xsd"> <Orders>';
        foreach ($repayments as $r) {
            $accountArr = explode("/", $r['account'], 2);
            $ret .= "<DomesticTransaction>";
            $ret .= "<accountFrom>" . $accountFromArr[0] . "</accountFrom>";
            $ret .= "<currency>CZK</currency>";
            $ret .= "<amount>" . $r['amount'] . "</amount>";
            $ret .= "<accountTo>" . $accountArr[0] . "</accountTo>";
            $ret .= "<bankCode>" . $accountArr[1] . "</bankCode>";
            $ret .= "<date>" . $date . "</date>";
            $ret .= "<messageForRecipient>" . $r['name'] . "</messageForRecipient>";
            $ret .= "<comment></comment>";
            $ret .= "<paymentType>431001</paymentType>";
            $ret .= "</DomesticTransaction>";
        }
        $ret .= "</Orders></Import>";
        return $ret;
    }

    public function sendFioPaymentRequest($stringToRequest, $token)
    {
        $curl = curl_init();
        $file = tempnam(WWW_DIR . "/../temp/", "XML"); // Vytvoření dočasného souboru s náhodným jménem v systémové temp složce.
        file_put_contents($file, $stringToRequest); // Do souboru se uloží XML string s vygenerovanými příkazy k úhradě.

        $this->curl_custom_postfields($curl, [
            'type' => 'xml',
            'token' => $token,
            'lng' => 'cs',
        ], ["file" => $file]);

        curl_setopt($curl, CURLOPT_URL, 'https://www.fio.cz/ib_api/rest/import/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        $resultXML = curl_exec($curl); // Odpověď z banky.
        curl_close($curl);

        unlink($file);
        return $resultXML;
    }

    /**
     * For safe multipart POST request for PHP5.3 ~ PHP 5.4.
     *
     * @param resource $ch cURL resource
     * @param array $assoc "name => value"
     * @param array $files "name => path"
     * @return bool
     */
    private function curl_custom_postfields($ch, array $assoc = [], array $files = [])
    {

        // invalid characters for "name" and "filename"
        static $disallow = ["\0", "\"", "\r", "\n"];

        $body = [];

        // build normal parameters
        foreach ($assoc as $k => $v) {
            $k = str_replace($disallow, "_", $k);
            $body[] = implode("\r\n", [
                "Content-Disposition: form-data; name=\"{$k}\"",
                "",
                filter_var($v),
            ]);
        }

        // build file parameters
        foreach ($files as $k => $v) {
            switch (TRUE) {
                case FALSE === $v = realpath(filter_var($v)):
                case!is_file($v):
                case!is_readable($v):
                    continue; // or return false, throw new InvalidArgumentException
            }
            $data = file_get_contents($v);
            $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
            $k = str_replace($disallow, "_", $k);
            $v = str_replace($disallow, "_", $v);
            $body[] = implode("\r\n", [
                "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
                "Content-Type: application/octet-stream",
                "",
                $data,
            ]);
        }

        // generate safe boundary
        do {
            $boundary = "---------------------" . md5(mt_rand() . microtime(TRUE));
        } while (preg_grep("/{$boundary}/", $body));

        // add boundary for each parameters
        array_walk($body, function (&$part) use ($boundary) {
            $part = "--{$boundary}\r\n{$part}";
        });

        // add final boundary
        $body[] = "--{$boundary}--";
        $body[] = "";

        // set options
        return @curl_setopt_array($ch, [
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => implode("\r\n", $body),
            CURLOPT_HTTPHEADER => [
                "Expect: 100-continue",
                "charset=utf-8",
                "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
            ],
        ]);
    }


    public function generateVs(int $gid): int
    {
        $nextVS = $this->getNextVS($gid);
        $payments = $this->payments->findByGroup($gid);

        $payments = array_filter($payments, function(Payment $p) {
            return $p->getVariableSymbol() === NULL && $p->getState()->equalsValue(State::PREPARING);
        });

        foreach ($payments as $payment) {
            $payment->updateVariableSymbol($nextVS++);
        }

        $this->payments->saveMany($payments);

        return count($payments);
    }


}
