<?php

declare(strict_types=1);

namespace Model\Common;

abstract class Aggregate
{
    /** @var object[] */
    private $eventsToDispatch = [];

    /**
     * Adds event to aggregate event list,
     * so it can be published to event bus before saving
     */
    protected function raise(object $event) : void
    {
        $this->eventsToDispatch[] = $event;
    }

    /**
     * Returns events to dispatch and clears events collection
     *
     * @return object[]
     */
    public function extractEventsToDispatch() : array
    {
        $events                 = $this->eventsToDispatch;
        $this->eventsToDispatch = [];

        return $events;
    }
}
