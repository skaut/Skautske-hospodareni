<?php

declare(strict_types=1);

namespace Model;

use Dibi\Row;
use function array_column;
use function array_combine;

class ParticipantTable extends BaseTable
{
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

}
