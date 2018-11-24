<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Participant\Event;

/**
 * @see PersonDaysQueryHandler
 */
final class PersonDaysQuery
{
    /** @var Event */
    private $event;

    public function __construct(Event $eventType)
    {
        $this->event = $eventType;
    }

    public function getEvent() : Event
    {
        return $this->event;
    }
}
