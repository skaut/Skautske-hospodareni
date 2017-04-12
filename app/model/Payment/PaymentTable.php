<?php

namespace Model;

use Dibi\Row;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentTable extends BaseTable
{

    const PAYMENT_STATE_PREPARING = "preparing";
    const PAYMENT_STATE_SEND = "send";

    public function getSimple(int $paymentId) : Row
    {
        return $this->connection->select('*')
            ->from(self::TABLE_PA_PAYMENT)
            ->where('id = %i', $paymentId)
            ->fetch();
    }

    /**
     * @param int|NULL $pa_groups
     * @return Row[]
     */
    public function getAllPayments($pa_groups)
    {
        return $this->connection->fetchAll("SELECT p.*, s.label as stateLabel FROM [" . self::TABLE_PA_PAYMENT . "] p "
            . "LEFT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID "
            . "WHERE groupId IN %in ", $pa_groups, " "
            . "ORDER BY s.orderby, p.name");
    }

    /**
     * @param int $pa_groupId
     * @return array
     */
    public function getActivePaymentIds($pa_groupId)
    {
        return $this->connection->fetchPairs("SELECT id, personId FROM [" . self::TABLE_PA_PAYMENT . "] WHERE groupId=%i ", $pa_groupId, " AND state != 'canceled'");
    }

    /**
     *
     * @param array $arr
     * @return bool
     */
    public function createPayment($arr): bool
    {
        return (bool)$this->connection->insert(self::TABLE_PA_PAYMENT, $arr)->execute();
    }

    /**
     * @param int $paymentId
     * @param array $arr
     * @param bool $notClosed
     * @return bool
     */
    public function update($paymentId, $arr, $notClosed = TRUE): bool
    {
        $q = $this->connection->update(self::TABLE_PA_PAYMENT, $arr)->where("id=%i", $paymentId);
        if ($notClosed) {
            $q->where("state in %in", $this->getNonFinalStates());
        }
        return (bool)$q->execute();
    }

    /**
     * seznam stavů, které jsou nedokončené
     * @return array
     */
    public function getNonFinalStates()
    {
        return [self::PAYMENT_STATE_PREPARING, self::PAYMENT_STATE_SEND];
    }

    /**
     * @param int|int[] $unitId
     * @param bool $onlyOpen
     * @return array
     */
    public function getGroups($unitId, $onlyOpen): array
    {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_PA_GROUP . "]"
            . " WHERE unitId IN %in ", !is_array($unitId) ? [$unitId] : $unitId, " AND state", "%if ", $onlyOpen, "='open' %else !='canceled' %end"
            . " ORDER BY id DESC")
            ->fetchAssoc("id");
    }

    /**
     * @param string $groupType
     * @param int $sisId
     * @return Row[]
     */
    public function getGroupsBySisId($groupType, $sisId)
    {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_PA_GROUP . "] WHERE groupType=%s ", $groupType, " AND sisId=%i ", $sisId, " AND state != 'canceled'");
    }

    /**
     * @param int $pa_groupId
     * @return array
     */
    public function summarizeByState($pa_groupId)
    {
        return $this->connection->query("SELECT s.label, SUM(amount) as amount, COUNT(p.id) as count FROM [" . self::TABLE_PA_PAYMENT . "] p"
            . " RIGHT JOIN [" . self::TABLE_PA_PAYMENT_STATE . "] s ON p.state = s.ID AND groupId=%i", $pa_groupId, " WHERE s.id != 'canceled'"
            . " GROUP BY s.id"
            . " ORDER BY s.orderby")->fetchAssoc("label");
    }

    /**
     * vrací nejvyšší hodnotu VS uvedenou ve skupině pro nezrušené platby
     * @param int $groupId
     * @return int
     */
    public function getNextVS($groupId) {
        $maxPaymentVs = $this->connection->fetchSingle("SELECT MAX(vs) FROM [" . self::TABLE_PA_PAYMENT . "] WHERE groupId=%i", $groupId, " AND state != 'canceled'");
        if(!is_null($maxPaymentVs) && !empty($maxPaymentVs)) {
            $maxPaymentVs = ++$maxPaymentVs;
        } else {
            $maxPaymentVs = $this->connection->fetchSingle("SELECT nextVs FROM [" . self::TABLE_PA_GROUP . "] WHERE id = %i", $groupId);
        }
        return $maxPaymentVs;
    }

    /**
     * vrací seznam id táborů se založenou aktivní skupinou
     */
    public function getCampIds(): array
    {
        return $this->connection->fetchPairs("SELECT sisId, label FROM [" . self::TABLE_PA_GROUP . "] WHERE groupType = 'camp' AND state != 'canceled' ");
    }

}
