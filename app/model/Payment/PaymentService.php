<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentService extends BaseService {

    protected $mailService;

    public function __construct($skautIS = NULL, $connection = NULL, MailService $mailService = NULL) {
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

    public function completePayment($pid, $transactionId = NULL) {
        return $this->update($pid, array("state" => "completed", "dateClosed" => date("Y-m-d H:i:s"), "transactionId" => $transactionId));
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
    public function sendInfo(\Nette\Application\UI\ITemplate $template, $payment, UnitService $us = NULL) {
        if ($payment->state != PaymentTable::PAYMENT_STATE_PREPARING || mb_strlen($payment->email) < 5) {
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

        $qrcode = '<img alt="QR platba" src="http://api.paylibo.com/paylibo/generator/czech/image?' . http_build_query($params) . '"/>';
        $body = str_replace(array("%account%", "%qrcode%", "%name%", "%amount%", "%maturity%", "%vs%", "%ks%", "%note%"), array($accountRaw, $qrcode, $payment->name, $payment->amount, $payment->maturity->format("j.n.Y"), $payment->vs, $payment->ks, $payment->note), $payment->email_info);
        if ($this->mailService->sendPaymentInfo($template, $payment->email, "Informace o platbě", $body, $payment->groupId)) {
            if (isset($payment->id)) {
                return $this->table->update($payment->id, array("state" => PaymentTable::PAYMENT_STATE_SEND));
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * číslo účtu jednotky ze skautisu
     * @param int $unitId
     * @return string|FALSE
     */
    protected function getBankAccount($unitId) {
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
    public function updateGroup($groupId, $arr) {
        return $this->table->updateGroup($groupId, $arr);
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
        } catch (\SkautIS\Exception\PermissionException $exc) {//odchycení bývalých členů, ke kterým už nemáme oprávnění
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
        $data = $this->skautis->org->UnitRegistrationAll(array("ID_Unit" => $unitId === NULL ? $unitId = $this->skautis->getUnitId() : $unitId, ""));
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
    public function getRegistrationPersons($units, $groupId = NULL) {
        $result = array();

        $group = $this->getGroup($units, $groupId);
        if (!$group) {
            throw new \InvalidArgumentException("Nebyla nalezena platební skupina");
        }
        $persons = $this->skautis->org->PersonRegistrationAll(array(
            'ID_UnitRegistration' => $group->sisId,
            'IncludeChild' => TRUE,
        ));

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

}
