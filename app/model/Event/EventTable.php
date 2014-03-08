<?php

/**
 * @author sinacek
 */
class EventTable extends BaseTable {

    public function getLocalId($skautisEventId, $type) {
        if (!($ret = dibi::fetchSingle("SELECT id FROM [" . self::TABLE_EVENT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $skautisEventId, $type))) {
            $ret = dibi::insert(self::TABLE_EVENT, array("skautisId" => $skautisEventId, "type" => $type))->execute(dibi::IDENTIFIER);
        }
        return $ret;
    }

    public function getSkautisId($localId) {
        return dibi::fetchSingle("SELECT skautisId FROM [" . self::TABLE_EVENT . "] WHERE id=%i LIMIT 1", $localId);
    }

    public function getByEventId($evId, $type) {
        return dibi::fetch("SELECT id as localId, prefix FROM  [" . self::TABLE_EVENT . "] WHERE skautisId=%i AND type=%s LIMIT 1", $evId, $type);
    }

}