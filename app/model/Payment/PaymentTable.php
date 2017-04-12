<?php

namespace Model;

use Dibi\Row;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentTable extends BaseTable
{

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
