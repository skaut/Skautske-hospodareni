<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class EventTable extends BaseTable {

    /**
     * vyhleda akci a pokud tam není, tak založí její záznam
     * @param type $skautisEventId
     * @param type $type
     * @return localId
     */
    public function getLocalId($skautisEventId, $type) {
        if (!($ret = $this->connection->fetchSingle("SELECT id FROM [" . self::TABLE_EVENT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $skautisEventId, $type))) {
            $ret = $this->connection->insert(self::TABLE_EVENT, array("skautisId" => $skautisEventId, "type" => $type))->execute(\dibi::IDENTIFIER);
        }
        return $ret;
    }

    public function getSkautisId($localId) {
        return $this->connection->fetchSingle("SELECT skautisId FROM [" . self::TABLE_EVENT . "] WHERE id=%i LIMIT 1", $localId);
    }

    public function getByEventId($skautisEventId, $type) {
        $ret = $this->connection->fetch("SELECT id as localId, prefix FROM  [" . self::TABLE_EVENT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $skautisEventId, $type);
        if(!$ret){
            $this->getLocalId($skautisEventId, $type);
            $ret = $this->getByEventId($skautisEventId, $type);
        }
        return $ret;
    }

    public function updatePrefix($skautisEventId, $type, $prefix) {
        $this->getLocalId($skautisEventId, $type);//pro zajisteni, ze akce existuje v tabulce
        return $this->connection->query("UPDATE [" . self::TABLE_EVENT . "] SET prefix=%s", $prefix == "" ? NULL : $prefix, " WHERE skautisId=%i ", $skautisEventId, "AND type=%s LIMIT 1", $type);
    }

}
