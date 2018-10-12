<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Event\Event;
use Model\Event\SkautisEventId;
use Model\Skautis\Mapper;

final class EventFactory implements IEventFactory
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /** @var Mapper */
    private $mapper;


    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function create(\stdClass $skautisEvent) : Event
    {
        return new Event(
            new SkautisEventId($skautisEvent->ID),
            $skautisEvent->DisplayName,
            $skautisEvent->ID_Unit,
            $skautisEvent->Unit,
            $skautisEvent->ID_EventGeneralState,
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisEvent->StartDate),
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisEvent->EndDate),
            $skautisEvent->TotalDays ?? null,
            $skautisEvent->Location ?? null,
            $skautisEvent->RegistrationNumber,
            $skautisEvent->Note ?? null,
            $skautisEvent->ID_EventGeneralScope,
            $skautisEvent->ID_EventGeneralType
        );
    }
}
