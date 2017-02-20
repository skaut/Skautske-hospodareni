<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ParticipantTable extends BaseTable
{

    public function get($participantId)
    {
        return $this->connection->fetch("SELECT * FROM  [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE participantId=%i LIMIT 1", $participantId);
    }

    /**
     * seznam všech záznamů k dané akci
     * @param type $skautiEventId
     * @return type
     */
    public function getCampLocalDetails($skautiEventId)
    {
        return $this->connection->query("SELECT participantId, payment, repayment, isAccount FROM [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE actionId=%i", $skautiEventId)
            ->fetchAssoc("participantId");
    }

    /**
     * smaže záznam
     * @param type $participantId
     */
    public function deleteLocalDetail($participantId)
    {
        $this->connection->query("DELETE FROM [" . self::TABLE_CAMP_PARTICIPANT . "] WHERE participantId =%i", $participantId);
    }

    public function update($participantId, $updateData)
    {
        $ins = $updateData;
        $ins['participantId'] = $participantId;
        unset($updateData['actionId']); //to se neaktualizuje
        return $this->connection->query("INSERT INTO [" . self::TABLE_CAMP_PARTICIPANT . "]", $ins, "
            ON DUPLICATE KEY 
            UPDATE %a", $updateData);
    }

}
