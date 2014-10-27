<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentTable extends BaseTable {

    const STATE_PREPARING = "preparing";
    const STATE_SEND = "send";

    public function get($unitId, $paymentId) {
        return $this->connection->fetch("SELECT p.*, g.email_info, g.email_demand, g.state as groupState FROM [" . self::TABLE_PA_PAYMENT . "] p"
                        . " LEFT JOIN [" . self::TABLE_PA_GROUP . "] g ON g.id = p.groupId"
                        . " WHERE g.unitId=%i", $unitId, " AND p.id=%i ", $paymentId);
    }

    /**
     * 
     * @param int|NULL $pa_groups
     * @return type
     */
    public function getAllPayments($pa_groups) {
        return $this->connection->fetchAll("SELECT p.*, s.label as stateLabel FROM [" . self::TABLE_PA_PAYMENT . "] p LEFT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID WHERE groupId IN %in ", $pa_groups, " ORDER BY s.orderby");
    }

    public function getActivePaymentIds($pa_groupId) {
        return $this->connection->fetchPairs("SELECT id, personId FROM [" . self::TABLE_PA_PAYMENT . "] WHERE groupId=%i ", $pa_groupId, " AND state != 'canceled'");
    }

    public function createPayment($arr) {
        return $this->connection->insert(self::TABLE_PA_PAYMENT, $arr)->execute();
    }

    public function update($paymentId, $arr, $notClosed = TRUE) {
        $q = $this->connection->update(self::TABLE_PA_PAYMENT, $arr)->where("id=%i", $paymentId);
        if ($notClosed) {
            $q->where("state in %in", array(self::STATE_PREPARING, self::STATE_SEND));
        }
        return $q->execute();
    }

    public function getGroup($unitId, $id) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE id=%i ", $id, " AND unitId=%i ", $unitId, " AND state != 'canceled'");
    }

    public function getGroups($unitId, $onlyOpen) {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE unitId=%i ", $unitId, " AND state", "%if ", $onlyOpen, "='open' %else !='canceled' %end")->fetchAssoc("id");
    }

    public function getGroupsIn($unitIds, $onlyOpen) {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE unitId IN %in ", $unitIds, " AND state", "%if ", $onlyOpen, "='open' %else !='canceled' %end")->fetchAssoc("id");
    }

    public function getGroupsBySisId($groupType, $sisId) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE groupType=%s ", $groupType, " AND sisId=%i ", $sisId, " AND state != 'canceled'");
    }

    public function createGroup($arr) {
        return $this->connection->insert(self::TABLE_PA_GROUP, $arr)->execute(\dibi::IDENTIFIER);
    }

    public function summarizeByState($pa_groupId) {
        return $this->connection->query("SELECT s.label, SUM(amount) as amount, COUNT(p.id) as count FROM [" . self::TABLE_PA_PAYMENT . "] p"
                        . " RIGHT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID AND groupId=%i", $pa_groupId, " WHERE s.id != 'canceled'"
                        . " GROUP BY s.id"
                        . " ORDER BY s.orderby")->fetchAssoc("label");
    }

    public function updateGroup($groupId, $arr) {
        return $this->connection->update(self::TABLE_PA_GROUP, $arr)->where("id=%i", $groupId)->where("state='open'")->execute();
    }

    public function getBankToken($unitId) {
        return $this->connection->fetchSingle("SELECT token FROM [" . self::TABLE_PA_BANK . "] WHERE unitId=%i", $unitId);
    }

}
