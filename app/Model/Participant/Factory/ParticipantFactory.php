<?php

declare(strict_types=1);

namespace App\Model\Skautis\Factory;

use App\Model\Participant\Participant;
use App\Model\Participant\Payment;
use Cake\Chronos\ChronosDate;
use stdClass;

use function preg_match;
use function property_exists;

final class ParticipantFactory
{
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public static function create(stdClass $skautisParticipant, Payment $payment): Participant
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $skautisParticipant->Person, $matches);

        return new Participant(
            $skautisParticipant->ID,
            $skautisParticipant->ID_Person,
            $matches['first'],
            $matches['last'],
            $matches['nick'] ?? null,
            $skautisParticipant->Age ?? null,
            property_exists($skautisParticipant, 'Birthday') && $skautisParticipant->Birthday !== null
                ? new ChronosDate($skautisParticipant->Birthday)
                : (
                    property_exists($skautisParticipant, 'PersonBirthday') && $skautisParticipant->PersonBirthday !== null
                        ? new ChronosDate($skautisParticipant->PersonBirthday)
                        : null
                ),
            $skautisParticipant->Street ?? $skautisParticipant->PersonAddressStreet,
            $skautisParticipant->City ?? $skautisParticipant->PersonAddressCity,
            (int) ($skautisParticipant->Postcode ?? $skautisParticipant->PersonAddressPostcode),
            $skautisParticipant->State ?? $skautisParticipant->PersonAddressState ?? '',
            isset($skautisParticipant->ID_Unit) ? (int) $skautisParticipant->ID_Unit : null,
            $skautisParticipant->Unit ?? '',
            $skautisParticipant->UnitRegistrationNumber ?? $skautisParticipant->PersonUnitRegistrationNumber ?? '',
            (int) ($skautisParticipant->Days ?? 0),
            (bool) ($skautisParticipant->IsAccepted ?? false),
            $payment,
            $skautisParticipant->Category ?? null,
        );
    }
}
