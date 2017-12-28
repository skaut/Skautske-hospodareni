<?php

namespace Model\Event\Handlers\Event;

use Model\Event\Commands\Event\CloseEvent;
use Model\Event\Repositories\IEventRepository;

final class CloseEventHandler
{

    /** @var IEventRepository */
    private $events;

    public function __construct(IEventRepository $events)
    {
        $this->events = $events;
    }

    public function handle(CloseEvent $command): void
    {
        $event = $this->events->find($command->getEventId());
        $this->events->close($event);
    }

}
