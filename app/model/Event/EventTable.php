<?php

namespace Model;

use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookNumberPrefixQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;

/**
 * @todo Remove
 */
class EventTable extends BaseTable
{
    /**
     * @deprecated Use queries to obtain Cashbook prefix or ID
     * @see EventCashbookIdQuery
     * @see CampCashbookIdQuery
     * @see CashbookNumberPrefixQuery
     */
    public function getByEventId($skautisEventId, $type)
    {
        return $this->connection->fetch(
            "SELECT o.id as localId, c.chit_number_prefix as prefix FROM [" . self::TABLE_OBJECT . "] o JOIN ac_cashbook cb ON cb.id = o.id
            WHERE skautisId=%i AND o.type=%s LIMIT 1", $skautisEventId, $type
        );
    }


}
