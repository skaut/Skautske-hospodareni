<?php

declare(strict_types=1);

namespace Model\Common;

use function is_object;

abstract class AbstractAggregate
{
    /** @var object[] */
    private $eventsToDispatch = [];

    /**
     * @param object $event
     */
    protected function raise($event) : void
    {
        if (! is_object($event)) {
            throw new \InvalidArgumentException("Event's must be objects");
        }
        $this->eventsToDispatch[] = $event;
    }

    /**
     * Returns events to dispatch and clears events collection
     * @return object[]
     */
    public function extractEventsToDispatch() : array
    {
        $events                 = $this->eventsToDispatch;
        $this->eventsToDispatch = [];
        return $events;
    }
}
