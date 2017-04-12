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
    private function getNonFinalStates()
    {
        return [self::PAYMENT_STATE_PREPARING, self::PAYMENT_STATE_SEND];
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
     * vrací seznam id táborů se založenou aktivní skupinou
     */
    public function getCampIds(): array
    {
        return $this->connection->fetchPairs("SELECT sisId, label FROM [" . self::TABLE_PA_GROUP . "] WHERE groupType = 'camp' AND state != 'canceled' ");
    }

}
