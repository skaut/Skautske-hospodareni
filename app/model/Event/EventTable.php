<?php

namespace Model;

/**
 * @author Hána František
 */
class EventTable extends BaseTable {

    public function updatePrefix($skautisEventId, $type, $prefix) {
        $this->getLocalId($skautisEventId, $type); //pro zajisteni, ze akce existuje v tabulce
        return $this->connection->query("UPDATE [" . self::TABLE_OBJECT . "] SET prefix=%s", $prefix == "" ? NULL : $prefix, " WHERE skautisId=%i ", $skautisEventId, "AND type=%s LIMIT 1", $type);
    }

}
