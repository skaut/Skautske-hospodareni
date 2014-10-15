<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentTable extends BaseTable {
    
    const STATE_PREPARING = "preparing";
    const STATE_SEND = "send";


    public function get($paymentId){
        return $this->connection->fetch("SELECT p.*, g.email_info, g.email_demand, g.state as groupState FROM [" . self::TABLE_PA_PAYMENT. "] p"
                . " LEFT JOIN [".self::TABLE_PA_GROUP."] g ON g.id = p.groupId"
                . " WHERE p.id=%i ", $paymentId);
    }
    
    /**
     * 
     * @param int|NULL $pa_groupId
     * @return type
     */
    public function getAllPayments($pa_groupId) {
        return $this->connection->fetchAll("SELECT p.*, s.label as stateLabel FROM [" . self::TABLE_PA_PAYMENT . "] p LEFT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID %if", $pa_groupId != NULL, " WHERE groupId=%i ", $pa_groupId, "%end");
    }

    public function getActivePaymentIds($pa_groupId) {
        return $this->connection->fetchPairs("SELECT id, personId FROM [" . self::TABLE_PA_PAYMENT . "] WHERE groupId=%i ", $pa_groupId, " AND state != 'canceled'");
    }

    public function getGroup($id) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE id=%i ", $id, " AND state != 'canceled'");
    }

    public function getGroupsBySisId($sisId) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE sisId=%i ", $sisId, " AND state != 'canceled'");
    }

    public function getGroupsByObjectId($objectId, $onlyOpen) {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE objectId=%i ", $objectId, "%if ", $onlyOpen, " AND state='open' %end")->fetchAssoc("id");
    }

    public function createPayment($arr) {
        return $this->connection->insert(self::TABLE_PA_PAYMENT, $arr)->execute();
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

    public function update($paymentId, $arr, $notClosed = TRUE) {
        $q = $this->connection->update(self::TABLE_PA_PAYMENT, $arr)->where("id=%i", $paymentId);
        if ($notClosed) {
            $q->where("state in %in", array(self::STATE_PREPARING, self::STATE_SEND));
        }
        return $q->execute();
    }

}
