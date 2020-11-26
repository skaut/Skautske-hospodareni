<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Participant\Participant;
use Model\Participant\Payment;
use stdClass;
use function preg_match;

final class ParticipantFactory
{
    public static function create(stdClass $skautisParticipant, Payment $payment) : Participant
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $skautisParticipant->Person, $matches);

        return new Participant(
            $skautisParticipant->ID,
            $skautisParticipant->ID_Person,
            $matches['first'],
            $matches['last'],
            $matches['nick'] ?? null,
            $skautisParticipant->Age ?? null,
            isset($skautisParticipant->Birthday) ? new Date($skautisParticipant->Birthday) : null,
            $skautisParticipant->Street ?? $skautisParticipant->PersonAddressStreet,
            $skautisParticipant->City ?? $skautisParticipant->PersonAddressCity,
            (int) ($skautisParticipant->Postcode ?? $skautisParticipant->PersonAddressPostcode),
            $skautisParticipant->State ?? $skautisParticipant->PersonAddressState,
            isset($skautisParticipant->ID_Unit) ? (int) $skautisParticipant->ID_Unit : null,
            $skautisParticipant->Unit ?? '',
            $skautisParticipant->UnitRegistrationNumber ?? '',
            (int) ($skautisParticipant->Days ?? 0),
            $payment,
            $skautisParticipant->Category ?? null
        );
    }
}
