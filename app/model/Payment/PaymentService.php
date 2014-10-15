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

    public function get($paymentId) {
        return $this->table->get($paymentId);
    }

    public function getAll($pa_groupId = NULL) {
        $result = $this->table->getAllPayments($pa_groupId);
        if ($pa_groupId === NULL) {
            $tmp = array();
            foreach ($result as $v) {//roztrizeni podle událostí
                $tmp[$v->groupId][] = $v;
            }
            $result = $tmp;
        }
        return $result;
    }

    public function createPayment($pa_oid, $name, $email, $amount, $personId = NULL, $maturity = NULL, $vs = NULL, $ks = NULL, $note = NULL) {
        return $this->table->createPayment(array(
                    'groupId' => $pa_oid,
                    'name' => $name,
                    'email' => $email,
                    'personId' => $personId,
                    'amount' => $amount,
                    'maturity' => $maturity,
                    'vs' => $vs,
                    'ks' => $ks,
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

    public function sendInfo($template, $paymentId, $unitId) {
        $p = $this->get($paymentId);
        if ($p->state != PaymentTable::STATE_PREPARING || mb_strlen($p->email) < 5) {
            return false;
        }
        $accounts = $this->skautIS->org->AccountAll(array("ID_Unit" => $unitId, "IsValid" => TRUE));
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
        $qrcode = '<img alt="QR platba" src="http://api.paylibo.com/paylibo/generator/czech/image?accountPrefix=' . $account['prefix'] . '&accountNumber=' . $account['number'] . '&bankCode=' . $account['code'] . '&amount=' . $p->amount . '&currency=CZK&vs=' . $p->vs . '&ks=' . $p->ks . '&message=' . $p->name . '&size=300"/>';
        $body = str_replace(array("%account%", "%qrcode%", "%name%", "%amount%", "%maturity%", "%vs%", "%ks%"), array($accountRaw, $qrcode, $p->name, $p->amount, $p->maturity->format("j.n.Y"), $p->vs, $p->ks), $p->email_info);
        if ($this->mailService->sendPaymentInfo($template, $p->email, "Informace o platbě", $body)) {
            return $this->table->update($paymentId, array("state" => "send"));
        }
        return FALSE;
    }

    public function getGroup($id) {
        return $this->table->getGroup($id);
    }

    public function getGroups($onlyOpen = TRUE) {
        return $this->table->getGroupsByObjectId($this->getLocalId($this->skautIS->getUnitId(), "unit"), $onlyOpen);
    }

    public function createGroup($oType, $sisId, $label, $oId = NULL, $maturity = NULL, $ks = NULL, $amount = NULL, $email_info = NULL, $email_demand = NULL) {
        if ($oId === NULL) {
            $oId = $this->getLocalId($this->skautIS->getUnitId(), "unit");
        }
        return $this->table->createGroup(array(
                    'groupType' => $oType,
                    'sisId' => $sisId,
                    'objectId' => $oId,
                    'label' => $label,
                    'maturity' => $maturity,
                    'ks' => $ks,
                    'amount' => $amount,
                    'email_info' => $email_info,
                    'email_demand' => $email_demand,
        ));
    }

    /**
     * detail registrace ze skautisu
     * @param int $regId
     * @return type
     */
    public function getRegistration($regId) {
        return $this->skautIS->org->UnitRegistrationDetail(array("ID" => $regId));
    }

    public function getNewestOpenRegistration($unitId = NULL, $withoutRecord = TRUE) {
        $data = $this->skautIS->org->UnitRegistrationAll(array("ID_Unit" => $unitId === NULL ? $unitId = $this->skautIS->getUnitId() : $unitId, ""));
        foreach ($data as $r) {
            if ($r->IsDelivered || ($withoutRecord && $this->table->getGroupsBySisId($r->ID))) {//filtrování odevzdaných nebo těch se záznamem
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
    public function getRegistrationPersons($id, $onlyWithoutRecord = TRUE) {
        $persons = $this->skautIS->org->PersonRegistrationAll(array(
            'ID_UnitRegistration' => $this->getGroup($id)->sisId,
            'IncludeChild' => TRUE,
        ));
        if ($onlyWithoutRecord) {
            $payments_personIds = $this->table->getActivePaymentIds($id);
            $persons = array_filter($persons, function ($v) use ($payments_personIds) {
                return !in_array($v->ID_Person, $payments_personIds);
            });
        }

        $result = array();
        foreach ($persons as $p) {
            $result[$p->ID_Unit][$p->ID_Person] = (array) $p;
            $result[$p->ID_Unit][$p->ID_Person]['emails'] = array();
            try {
                foreach ($this->skautIS->org->PersonContactAll(array('ID_Person' => $p->ID_Person)) as $c) {
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
