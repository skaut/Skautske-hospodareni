<?php

namespace Model;

/**
 * @author sinacek
 */
class ParticipantTable extends BaseTable {

    public function get($participantId) {
        return $this->connection->fetch("SELECT * FROM  [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE participantId=%i LIMIT 1", $participantId);
    }

    /**
     * seznam všech záznamů k dané akci
     * @param type $actionId
     * @return type
     */
    public function getAll($actionId) {
        return $this->connection->fetchAll("SELECT participantId, payment, repayment, isAccount FROM [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE actionId=%i", $actionId);
    }

    /**
     * smaže záznam
     * @param type $participantId
     */
    public function deleteDetail($participantId) {
        $this->connection->query("DELETE FROM [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE participantId =%i", $participantId);
    }

    public function update($participantId, $updateData) {
        $ins = $updateData;
        $ins['participantId'] = $participantId;
        unset($updateData['actionId']); //to se neaktualizuje
        return $this->connection->query("INSERT INTO [" . self::TABLE_CAMP_PARTICIPANT . "]", $ins, "
            ON DUPLICATE KEY 
            UPDATE %a", $updateData);
    }

}