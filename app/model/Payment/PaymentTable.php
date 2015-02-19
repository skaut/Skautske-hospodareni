<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentTable extends BaseTable {

    const PAYMENT_STATE_PREPARING = "preparing";
    const PAYMENT_STATE_SEND = "send";

    /**
     * 
     * @param int|array $unitId
     * @param int $paymentId
     * @return \DibiRow
     */
    public function get($unitId, $paymentId) {
        return $this->connection->fetch("SELECT p.*, g.email_info, g.email_demand, g.state as groupState, g.unitId FROM [" . self::TABLE_PA_PAYMENT . "] p"
                        . " LEFT JOIN [" . self::TABLE_PA_GROUP . "] g ON g.id = p.groupId"
                        . " WHERE g.unitId IN %in", !is_array($unitId) ? array($unitId) : $unitId, " AND p.id=%i ", $paymentId);
    }

    /**
     * 
     * @param int|NULL $pa_groups
     * @return type
     */
    public function getAllPayments($pa_groups) {
        return $this->connection->fetchAll("SELECT p.*, s.label as stateLabel FROM [" . self::TABLE_PA_PAYMENT . "] p "
                        . "LEFT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID "
                        . "WHERE groupId IN %in ", $pa_groups, " "
                        . "ORDER BY s.orderby, p.name");
    }

    /**
     * 
     * @param int $pa_groupId
     * @return array
     */
    public function getActivePaymentIds($pa_groupId) {
        return $this->connection->fetchPairs("SELECT id, personId FROM [" . self::TABLE_PA_PAYMENT . "] WHERE groupId=%i ", $pa_groupId, " AND state != 'canceled'");
    }

    /**
     * 
     * @param array $arr
     * @return type
     */
    public function createPayment($arr) {
        return $this->connection->insert(self::TABLE_PA_PAYMENT, $arr)->execute();
    }

    /**
     * 
     * @param int $paymentId
     * @param array $arr
     * @param bool $notClosed
     * @return type
     */
    public function update($paymentId, $arr, $notClosed = TRUE) {
        $q = $this->connection->update(self::TABLE_PA_PAYMENT, $arr)->where("id=%i", $paymentId);
        if ($notClosed) {
            $q->where("state in %in", $this->getNonFinalStates());
        }
        return $q->execute();
    }

    /**
     * seznam stavů, které jsou nedokončené
     * @return array
     */
    public function getNonFinalStates() {
        return array(self::PAYMENT_STATE_PREPARING, self::PAYMENT_STATE_SEND);
    }

    /**
     * 
     * @param int|array(int) $unitId
     * @param int $id
     * @return \DibiRow
     */
    public function getGroup($unitId, $id) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE id=%i ", $id, " AND unitId IN %in ", !is_array($unitId) ? array($unitId) : $unitId, " AND state != 'canceled'");
    }

    /**
     * 
     * @param int|array(int) $unitId
     * @param bool $onlyOpen
     * @return array
     */
    public function getGroups($unitId, $onlyOpen) {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_PA_GROUP . "]"
                                . " WHERE unitId IN %in ", !is_array($unitId) ? array($unitId) : $unitId, " AND state", "%if ", $onlyOpen, "='open' %else !='canceled' %end"
                                . " ORDER BY id DESC")
                        ->fetchAssoc("id");
    }

    /**
     * 
     * @param string $groupType
     * @param int $sisId
     * @return \DibiRow[]
     */
    public function getGroupsBySisId($groupType, $sisId) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE groupType=%s ", $groupType, " AND sisId=%i ", $sisId, " AND state != 'canceled'");
    }

    /**
     * 
     * @param array $arr
     * @return type
     */
    public function createGroup($arr) {
        return $this->connection->insert(self::TABLE_PA_GROUP, $arr)->execute(\dibi::IDENTIFIER);
    }

    /**
     * 
     * @param type $pa_groupId
     * @return array
     */
    public function summarizeByState($pa_groupId) {
        return $this->connection->query("SELECT s.label, SUM(amount) as amount, COUNT(p.id) as count FROM [" . self::TABLE_PA_PAYMENT . "] p"
                        . " RIGHT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID AND groupId=%i", $pa_groupId, " WHERE s.id != 'canceled'"
                        . " GROUP BY s.id"
                        . " ORDER BY s.orderby")->fetchAssoc("label");
    }

    /**
     * 
     * @param int $groupId
     * @param array $arr
     * @return type
     */
    public function updateGroup($groupId, $arr) {
        return $this->connection->update(self::TABLE_PA_GROUP, $arr)->where("id=%i", $groupId)->where("state='open'")->execute();
    }

    /**
     * vrací nejvyšší hodnotu VS uvedenou ve skupině pro nezrušené platby
     * @param int $groupId
     * @return int
     */
    public function getMaxVS($groupId) {
        return $this->connection->fetchSingle("SELECT MAX(vs) FROM [" . self::TABLE_PA_PAYMENT . "] WHERE groupId=%i", $groupId, " AND state != 'canceled'");
    }

}
