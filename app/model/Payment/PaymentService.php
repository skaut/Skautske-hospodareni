<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentService extends BaseService {

    /**
     *
     * @var MailService
     */
    protected $mailService;

    public function __construct(\Skautis\Skautis $skautIS, \Dibi\Connection $connection, MailService $mailService) {
        parent::__construct($skautIS, $connection);
        $this->mailService = $mailService;
    }

    public function get($unitId, $paymentId) {
        return $this->table->get($unitId, $paymentId);
    }

    /**
     * 
     * @param int|array $pa_groupIds
     * @return array
     */
    public function getAll($pa_groupIds, $useHierarchy = FALSE) {
        $result = $this->table->getAllPayments(is_array($pa_groupIds) ? $pa_groupIds : array($pa_groupIds));
        if ($useHierarchy) {
            $tmp = array();
            foreach ($result as $v) {//roztrizeni podle událostí
                $tmp[$v->groupId][] = $v;
            }
            $result = $tmp;
        }
        return $result;
    }

    /**
     * 
     * @param int $groupId
     * @param string $name
     * @param srting $email
     * @param float $amount
     * @param type $maturity
     * @param int $personId
     * @param int $vs
     * @param int $ks
     * @param string $note
     * @return type
     */
    public function createPayment($groupId, $name, $email, $amount, $maturity, $personId = NULL, $vs = NULL, $ks = NULL, $note = NULL) {
        return $this->table->createPayment(array(
                    'groupId' => $groupId,
                    'name' => $name,
                    'email' => $email,
                    'personId' => $personId,
                    'amount' => $amount != "" ? $amount : NULL,
                    'maturity' => $maturity,
                    'vs' => $vs != "" ? $vs : NULL,
                    'ks' => $ks != "" ? $ks : NULL,
                    'note' => $note,
        ));
    }

    /**
     * 
     * @param int $pid
     * @param array $arr
     * @return type
     */
    public function update($pid, $arr) {
        return $this->table->update($pid, $arr);
    }

    public function cancelPayment($pid) {
        return $this->update($pid, array("state" => "canceled", "dateClosed" => date("Y-m-d H:i:s")));
    }

    public function completePayment($pid, $transactionId = NULL, $paidFrom = NULL) {
        return $this->update($pid, array("state" => "completed", "dateClosed" => date("Y-m-d H:i:s"), "transactionId" => $transactionId, "paidFrom" => $paidFrom));
    }

    /**
     * seznam stavů, které jsou nedokončené
     * @return array
     */
    public function getNonFinalStates() {
        return $this->table->getNonFinalStates();
    }

    /**
     * spočte částky v jednotlivých stavech platby
     * @param int $pa_groupId
     * @return array
     */
    public function summarizeByState($pa_groupId) {
        return $this->table->summarizeByState($pa_groupId);
    }

    /**
     * 
     * @param \Nette\Application\UI\ITemplate $template
     * @param type $payment - state=PaymentTable::PAYMENT_STATE_PREPARING, email, unitId, amount, maturity, email_info, name, note, vs, ks
     * @param \Model\UnitService $us
     * @return boolean
     */
    public function sendInfo(\Nette\Application\UI\ITemplate $template, $payment, $group, UnitService $us = NULL) {
        if (!in_array($payment->state, $this->getNonFinalStates()) || mb_strlen($payment->email) < 5) {
            return FALSE;
        }
        $oficialUnitId = $us->getOficialUnit($payment->unitId)->ID;
        $accountRaw = $this->getBankAccount($oficialUnitId);
        preg_match('#((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})#', $accountRaw, $account);

        $params = array(
            "accountNumber" => $account['number'],
            "bankCode" => $account['code'],
            "amount" => $payment->amount,
            "currency" => "CZK",
            "date" => $payment->maturity->format("Y-m-d"),
            "size" => "200",
        );
        if (array_key_exists('prefix', $account) && $account['prefix'] != '') {
            $params['accountPrefix'] = $account['prefix'];
        }
        if ($payment->vs != '') {
            $params['vs'] = $payment->vs;
        }
        if ($payment->ks != '') {
            $params['ks'] = $payment->ks;
        }
        if ($payment->name != '') {
            $params['message'] = $payment->name;
        }

        //$base64 = 'data:image/png;base64,' . base64_encode(file_get_contents("http://api.paylibo.com/paylibo/generator/czech/image?" . http_build_query($params)));
        //$qrcode = '<img alt="QR platba" src="' . $base64 . '"/>';

        $qrUrl = 'http://api.paylibo.com/paylibo/generator/czech/image?' . http_build_query($params);
        $qrPrefix = $qrFilename = NULL;
        if (strpos($payment->email_info, "%qrcode%")) {
            $qrPrefix = WWW_DIR . "/webtemp/";
            $qrFilename = "qr_" . date("y_m_d_H_i_s_") . (rand(10, 20) * microtime()) . ".png";
            \Nette\Utils\Image::fromFile($qrUrl)->save($qrPrefix . $qrFilename);
//            dump(is_readable($qrPrefix . $qrFilename));
//            die();
        }
        $qrcode = '<img alt="QR platbu se nepodařilo zobrazit" src="' . $qrFilename . '"/>';
        $body = str_replace(
                array("%account%", "%qrcode%", "%name%", "%groupname%", "%amount%", "%maturity%", "%vs%", "%ks%", "%note%"), 
                array($accountRaw, $qrcode, $payment->name, $group->label, $payment->amount, $payment->maturity->format("j.n.Y"), $payment->vs, $payment->ks, $payment->note), $payment->email_info
                );
        if (($mailSend = ($this->mailService->sendPaymentInfo($template, $payment->email, "Informace o platbě", $body, $payment->groupId, $qrPrefix)))) {
            if (isset($payment->id)) {
                $this->table->update($payment->id, array("state" => PaymentTable::PAYMENT_STATE_SEND));
            }
        }
        if (is_file($qrPrefix . $qrFilename)) {
            unlink($qrPrefix . $qrFilename);
        }
        return $mailSend ? TRUE : FALSE;
    }

    /**
     * číslo účtu jednotky ze skautisu
     * @param int $unitId
     * @return string|FALSE
     */
    public function getBankAccount($unitId) {
        $accounts = $this->skautis->org->AccountAll(array("ID_Unit" => $unitId, "IsValid" => TRUE));
        if (count($accounts) == 1) {
            return $accounts[0]->DisplayName;
        } else {
            foreach ($accounts as $a) {//vyfiltrování hlavního emailu
                if ($a->IsMain) {
                    return $a->DisplayName;
                }
            }
        }
        return FALSE;
    }

    /**
     * GROUP
     */

    /**
     * 
     * @param int|array(int) $unitId
     * @param int $groupId
     * @return type
     */
    public function getGroup($unitId, $groupId) {
        return $this->table->getGroup($unitId, $groupId);
    }

    /**
     * 
     * @param int|array(int) $unitId
     * @param boolean $onlyOpen
     * @return type
     */
    public function getGroups($unitId, $onlyOpen = FALSE) {
        return $this->table->getGroups($unitId, $onlyOpen);
    }

    /**
     * 
     * @param int $unitId
     * @param string $oType
     * @param int $sisId
     * @param string $label
     * @param type $maturity
     * @param int $ks
     * @param float $amount
     * @param string $email_info
     * @param string $email_demand
     * @return type
     */
    public function createGroup($unitId, $oType, $sisId, $label, $maturity = NULL, $ks = NULL, $amount = NULL, $email_info = NULL, $email_demand = NULL) {
        return $this->table->createGroup(array(
                    'groupType' => $oType,
                    'sisId' => $sisId,
                    'unitId' => $unitId,
                    'label' => $label,
                    'maturity' => $maturity,
                    'ks' => $ks != "" ? $ks : NULL,
                    'amount' => $amount != "" ? $amount : NULL,
                    'email_info' => $email_info,
                    'email_demand' => $email_demand,
        ));
    }

    /**
     * 
     * @param int $groupId
     * @param array $arr
     * @return type
     */
    public function updateGroup($groupId, $arr, $openOnly = TRUE) {
        return $this->table->updateGroup($groupId, $arr, $openOnly);
    }

    /**
     * vrací nejvyšší hodnotu VS uvedenou ve skupině pro nezrušené platby
     * @param int $groupId
     * @return int
     */
    public function getMaxVS($groupId) {
        return $this->table->getMaxVS($groupId);
    }

    /**
     * seznam osob z dané jednotky
     * @param type $unitId
     * @param type $groupId - skupina plateb, podle které se filtrují osoby, které již mají platbu zadanou
     * @return array($personId => array(...))
     */
    public function getPersons($unitId, $groupId = NULL) {
        $result = array();
        $persons = $this->skautis->org->PersonAll(array("ID_Unit" => $unitId, "OnlyDirectMember" => TRUE));
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
                $result[$p->ID] = (array) $p;
                $result[$p->ID]['emails'] = $this->getPersonEmails($p->ID);
            }
        }
        return $result;
    }

    /**
     * vrací seznam emailů osoby
     * @param type $personId
     * @return string
     */
    public function getPersonEmails($personId) {
        $result = array();
        try {
            $emails = $this->skautis->org->PersonContactAll(array('ID_Person' => $personId));
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
     * detail registrace ze skautisu
     * @param int $regId
     * @return type
     */
    public function getRegistration($regId) {
        return $this->skautis->org->UnitRegistrationDetail(array("ID" => $regId));
    }

    public function getNewestOpenRegistration($unitId = NULL, $withoutRecord = TRUE) {
        $data = $this->skautis->org->UnitRegistrationAll(array("ID_Unit" => $unitId === NULL ? $unitId = $this->skautis->getUser()->getUnitId() : $unitId, ""));
        foreach ($data as $r) {
            if ($r->IsDelivered || ($withoutRecord && $this->table->getGroupsBySisId('registration', $r->ID))) {//filtrování odevzdaných nebo těch se záznamem
                continue;
            }
            return (array) $r;
        }
        return FALSE;
    }

    /**
     * seznam osob z registrace
     * @param int|array $units
     * @param int $groupId ID platebni skupiny, podle ktere se filtruji osoby bez platby
     * @return array(array())
     */
    public function getPersonsFromRegistrationWithoutPayment($units, $groupId = NULL) {
        $result = array();

        $group = $this->getGroup($units, $groupId);
        if (!$group) {
            throw new \InvalidArgumentException("Nebyla nalezena platební skupina");
        }
        $persons = $this->getPersonFromRegistration($group->sisId, TRUE);

        if (is_array($persons)) {
            usort($persons, function ($a, $b) {
                return strcmp($a->Person, $b->Person);
            });
            if ($groupId !== NULL) {
                $payments_personIds = $this->table->getActivePaymentIds($groupId);
                $persons = array_filter($persons, function ($v) use ($payments_personIds) {
                    return !in_array($v->ID_Person, $payments_personIds);
                });
            }

            foreach ($persons as $p) {
                $result[$p->ID_Person] = (array) $p;
                $result[$p->ID_Person]['emails'] = $this->getPersonEmails($p->ID_Person);
            }
        }
        return $result;
    }

    public function getPersonFromRegistration($registrationId, $includeChild = TRUE) {
        return($this->skautis->org->PersonRegistrationAll(array(
                    'ID_UnitRegistration' => $registrationId,
                    'IncludeChild' => $includeChild,
        )));
    }

    /**
     * JOURNAL
     */

    /**
     * @param int $unitId
     * @param int $year
     * @return array("add" => [], "remove" => []) | NULL
     */
    public function getJournalChangesAfterRegistration($unitId, $year) {
        $registrations = $this->skautis->org->UnitRegistrationAll(array("ID_Unit" => $unitId, "Year" => $year));
        if (!is_array($registrations) || count($registrations) < 1) {
            return NULL;
        }
        $registrationId = reset($registrations)->ID;
        $registration = $this->getPersonFromRegistration($registrationId, FALSE);

        $regCategories = [];
        foreach ($this->skautis->org->RegistrationCategoryAll(array("ID_UnitRegistration" => $registrationId)) as $rc) {
            $regCategories[$rc->ID] = $rc->IsJournal;
        }
        $unitJournals = $this->skautis->Journal->PersonJournalAllUnit(array("ID_Unit" => $unitId, "ShowHistory" => FALSE, "IncludeChild" => TRUE));

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
        return($changes);
    }

    /**
     * CAMP
     */
    public function getCamp($campId) {
        return $this->skautis->event->{"EventCampDetail"}(array("ID" => $campId));
    }

    public function getGroupByCampId($campId) {
        $g = $this->table->getGroupsBySisId('camp', $campId);
        return empty($g) ? FALSE : $g[0];
    }

    /**
     * vrací seznam id táborů se založenou aktivní skupinou
     */
    public function getCampIds() {
        return $this->table->getCampIds();
    }

    /* Repayments */

    public function getFioRepaymentString($repayments, $accountFrom, $date = NULL) {
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

    public function sendFioPaymentRequest($stringToRequest, $token) {
        $curl = curl_init();
        $file = tempnam(WWW_DIR . "/../temp/", "XML"); // Vytvoření dočasného souboru s náhodným jménem v systémové temp složce.
        file_put_contents($file, $stringToRequest); // Do souboru se uloží XML string s vygenerovanými příkazy k úhradě.
        try {
            //$cfile = new \CURLFile($file, 'application/xml', 'import.xml'); // Připraví soubor k odeslání přes cURL pro PHP 5.5 a vyšší
            $this->curl_custom_postfields($curl, array(
                'type' => 'xml',
                'token' => $token,
                'lng' => 'cs',
                    ), array("file" => $file));

            curl_setopt($curl, CURLOPT_URL, 'https://www.fio.cz/ib_api/rest/import/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_VERBOSE, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
//            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data; charset=utf-8;'));
//            curl_setopt($curl, CURLOPT_POSTFIELDS, array(
//                'type' => 'xml',
//                'token' => $token,
//                'lng' => 'cs',
//                'file' => $cfile
//            ));
            $resultXML = curl_exec($curl); // Odpověď z banky.
            curl_close($curl);
        } catch (Exception $e) {
            throw $e;
        }
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
    function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {

        // invalid characters for "name" and "filename"
        static $disallow = array("\0", "\"", "\r", "\n");

        // build normal parameters
        foreach ($assoc as $k => $v) {
            $k = str_replace($disallow, "_", $k);
            $body[] = implode("\r\n", array(
                "Content-Disposition: form-data; name=\"{$k}\"",
                "",
                filter_var($v),
            ));
        }

        // build file parameters
        foreach ($files as $k => $v) {
            switch (true) {
                case false === $v = realpath(filter_var($v)):
                case!is_file($v):
                case!is_readable($v):
                    continue; // or return false, throw new InvalidArgumentException
            }
            $data = file_get_contents($v);
            $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
            $k = str_replace($disallow, "_", $k);
            $v = str_replace($disallow, "_", $v);
            $body[] = implode("\r\n", array(
                "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
                "Content-Type: application/octet-stream",
                "",
                $data,
            ));
        }

        // generate safe boundary
        do {
            $boundary = "---------------------" . md5(mt_rand() . microtime());
        } while (preg_grep("/{$boundary}/", $body));

        // add boundary for each parameters
        array_walk($body, function (&$part) use ($boundary) {
            $part = "--{$boundary}\r\n{$part}";
        });

        // add final boundary
        $body[] = "--{$boundary}--";
        $body[] = "";

        // set options
        return @curl_setopt_array($ch, array(
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => implode("\r\n", $body),
                    CURLOPT_HTTPHEADER => array(
                        "Expect: 100-continue",
                        "charset=utf-8",
                        "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
                    ),
        ));
    }

}
