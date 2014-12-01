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
     * @return type
     */
    public function getAll($pa_groupIds, $useHierarchy = TRUE) {
        if (!is_array($pa_groupIds)) {
            $pa_groupIds = array($pa_groupIds);
        }
        $result = $this->table->getAllPayments($pa_groupIds);
        if (count($pa_groupIds) > 1 && $useHierarchy) {
            $tmp = array();
            foreach ($result as $v) {//roztrizeni podle událostí
                $tmp[$v->groupId][] = $v;
            }
            $result = $tmp;
        }
        return $result;
    }

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

    public function update($pid, $arr) {
        return $this->table->update($pid, $arr);
    }

    public function getNonFinalStates() {
        return array(PaymentTable::STATE_PREPARING, PaymentTable::STATE_SEND);
    }

    /**
     * spočte částky v jednotlivých stavech platby
     * @param int $pa_id
     * @return array
     */
    public function summarizeByState($pa_id) {
        return $this->table->summarizeByState($pa_id);
    }

    public function sendInfo($unitId, $template, $paymentId) {
        $p = $this->get($unitId, $paymentId);
        if ($p->state != PaymentTable::STATE_PREPARING || mb_strlen($p->email) < 5) {
            return false;
        }
        $accounts = $this->skautis->org->AccountAll(array("ID_Unit" => $unitId, "IsValid" => TRUE));
        if (count($accounts) == 1) {
            $accountRaw = $accounts[0]->DisplayName;
        } else {
            $accountRaw = FALSE;
            foreach ($accounts as $a) {//vyfiltrování hlavního emailu
                if ($a->IsMain) {
                    $accountRaw = $a->DisplayName;
                }
            }
        }
        preg_match('#((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})#', $accountRaw, $account);
        $qrcode = '<img alt="QR platba" src="http://api.paylibo.com/paylibo/generator/czech/image?accountPrefix=' . $account['prefix'] . '&accountNumber=' . $account['number'] . '&bankCode=' . $account['code'] . '&amount=' . $p->amount . '&currency=CZK&vs=' . $p->vs . '&ks=' . $p->ks . '&message=' . $p->name . '&size=200"/>';
        $body = str_replace(array("%account%", "%qrcode%", "%name%", "%amount%", "%maturity%", "%vs%", "%ks%", "%note%"), array($accountRaw, $qrcode, $p->name, $p->amount, $p->maturity->format("j.n.Y"), $p->vs, $p->ks, $p->note), $p->email_info);
        if ($this->mailService->sendPaymentInfo($template, $p->email, "Informace o platbě", $body)) {
            return $this->table->update($paymentId, array("state" => "send"));
        }
        return FALSE;
    }

    /**
     * GROUP
     */
    public function getGroup($unitId, $groupId) {
        return $this->table->getGroup($unitId, $groupId);
    }

    public function getGroups($unitId, $onlyOpen = FALSE) {
        return $this->table->getGroups($unitId, $onlyOpen);
    }

    public function getGroupsIn($unitIds, $onlyOpen) {
        return $this->table->getGroupsIn($unitIds, $onlyOpen);
    }

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

    public function updateGroup($groupId, $arr) {
        return $this->table->updateGroup($groupId, $arr);
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
     * @param int $id ID registrace
     * @param bool $onlyWithoutRecord pouze ty, které ještě nemají zadanou platbu
     * @return array(array())
     */
    public function getRegistrationPersons($unitId, $id, $onlyWithoutRecord = TRUE) {
        $persons = $this->skautis->org->PersonRegistrationAll(array(
            'ID_UnitRegistration' => $this->getGroup($unitId, $id)->sisId,
            'IncludeChild' => TRUE,
        ));
        if ($onlyWithoutRecord) {
            $payments_personIds = $this->table->getActivePaymentIds($id);
            if (is_array($persons)) {
                $persons = array_filter($persons, function ($v) use ($payments_personIds) {
                    return !in_array($v->ID_Person, $payments_personIds);
                });
            }
        }

        $result = array();
        foreach ($persons as $p) {
            $result[$p->ID_Unit][$p->ID_Person] = (array) $p;
            $result[$p->ID_Unit][$p->ID_Person]['emails'] = array();
            try {
                foreach ($this->skautis->org->PersonContactAll(array('ID_Person' => $p->ID_Person)) as $c) {
                    if (mb_substr($c->ID_ContactType, 0, 5) == "email") {
                        $result[$p->ID_Unit][$p->ID_Person]['emails'][$c->Value] = $c->Value . " (" . $c->ContactType . ")";
                    }
                }
            } catch (\SkautIS\Exception\PermissionException $exc) {//odchycení bývalých členů, ke kterým už nemáme oprávnění
                $result[$p->ID_Unit][$p->ID_Person]['emails'] = array();
            }
        }
        return $result;
    }

}
