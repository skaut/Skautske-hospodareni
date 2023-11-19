<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\ChronosDate;
use Model\Common\UnitId;
use Model\Event\Event;
use Model\Event\SkautisEventId;
use stdClass;

use function explode;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EventFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    private function createDateFromFormat(string $str): ChronosDate
    {
        $matches = explode('.', $str, 2); // remove microseconds

        return ChronosDate::createFromFormat(self::DATETIME_FORMAT, $matches[0]);
    }

    public function create(stdClass $skautisEvent): Event
    {
        return new Event(
            new SkautisEventId($skautisEvent->ID),
            $skautisEvent->DisplayName,
            new UnitId($skautisEvent->ID_Unit),
            $skautisEvent->Unit,
            $skautisEvent->ID_EventGeneralState,
            $this->createDateFromFormat($skautisEvent->StartDate),
            $this->createDateFromFormat($skautisEvent->EndDate),
            $skautisEvent->TotalDays ?? null,
            $skautisEvent->Location ?? null,
            $skautisEvent->RegistrationNumber,
            $skautisEvent->Note ?? null,
            $skautisEvent->ID_EventGeneralScope,
            $skautisEvent->ID_EventGeneralType,
            $skautisEvent->IsStatisticAutoComputed ?? null,
            $skautisEvent->TotalParticipants ?? null,
            $skautisEvent->ChildDays ?? null,
            $skautisEvent->PersonDays ?? null,
            $skautisEvent->PersonClosed ?? null,
            isset($skautisEvent->DateClosed) ? ChronosDate::createFromFormat('Y-m-d', explode('T', $skautisEvent->DateClosed)[0]) : null,
            $skautisEvent->UnitEducative ?? null,
        );
    }
}
