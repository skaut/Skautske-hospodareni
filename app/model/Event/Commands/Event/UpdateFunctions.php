<?php

namespace Model\Event\Commands\Event;

use Model\Event\Functions;

final class UpdateFunctions
{

    /** @var int */
    private $eventId;

    /** @var Functions */
    private $functions;

    public function __construct(int $eventId, Functions $functions)
    {
        $this->eventId = $eventId;
        $this->functions = $functions;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getFunctions(): Functions
    {
        return $this->functions;
    }

}
