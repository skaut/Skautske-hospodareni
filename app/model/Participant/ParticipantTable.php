<?php

/**
 * @author Hána František
 */
class ParticipantTable extends BaseTable {

    public function get($participantId) {
        return dibi::fetch("SELECT * FROM  [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE participantId =%i LIMIT 1", $participantId);
    }

    /**
     * seznam všech záznamů k dané akci
     * @param type $actionId
     * @return type
     */
    public function getAll($actionId) {
        return dibi::fetchAll("SELECT participantId, payment, repayment, isAccount FROM [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE actionId=%i", $actionId);
    }
    
    /**
     * smaze záznam
     * @param type $participantId
     */
    public function deleteDetail($participantId){
        dibi::query("DELETE FROM [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE participantId =%i", $participantId);
    }
    
    public function update($participantId, $updateData){
        $ins = $updateData;
        $ins['participantId'] = $participantId;
        unset($updateData['actionId']);//to se neaktualizuje
        return dibi::query("INSERT INTO [" . self::TABLE_CAMP_PARTICIPANT . "]", $ins,"
            ON DUPLICATE KEY 
            UPDATE %a", $updateData);
    }

//    public function add($values) {
//        dibi::query("INSERT INTO [" . self::TABLE_CHIT . "] %v", $values);
//        return dibi::getInsertId();
//    }

}