<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Participant\Event;

/**
 * @see ParticipantListQueryHandler
 */
final class ParticipantListQuery
{
    /** @var Event */
    private $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getEvent() : Event
    {
        return $this->event;
    }
}
