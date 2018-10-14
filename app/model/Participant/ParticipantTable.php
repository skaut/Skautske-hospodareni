<?php

declare(strict_types=1);

namespace Model;

use Dibi\Row;
use function array_column;
use function array_combine;

class ParticipantTable extends BaseTable
{
    /**
     * @return Row|false
     */
    public function get(int $participantId)
    {
        return $this->connection->fetch('SELECT * FROM  [' . self::TABLE_CAMP_PARTICIPANT . '] WHERE participantId=%i LIMIT 1', $participantId);
    }

    /**
     * seznam všech záznamů k dané akci
     *
     * @return Row[] Details indexed by participant ID
     */
    public function getCampLocalDetails(int $skautiEventId) : array
    {
        $participants = $this->connection->fetchAll('SELECT participantId, payment, repayment, isAccount FROM [' . self::TABLE_CAMP_PARTICIPANT . '] WHERE actionId=%i', $skautiEventId);

        return array_combine(array_column($participants, 'participantId'), $participants);
    }

    public function deleteLocalDetail(int $participantId) : void
    {
        $this->connection->query('DELETE FROM [' . self::TABLE_CAMP_PARTICIPANT . '] WHERE participantId =%i', $participantId);
    }

    /**
     * @param mixed[] $updateData
     */
    public function update(int $participantId, array $updateData) : void
    {
        $ins                  = $updateData;
        $ins['participantId'] = $participantId;
        unset($updateData['actionId']); //to se neaktualizuje
        $this->connection->query(
            'INSERT INTO [' . self::TABLE_CAMP_PARTICIPANT . ']',
            $ins,
            '
            ON DUPLICATE KEY 
            UPDATE %a',
            $updateData
        );
    }
}
