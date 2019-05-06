<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Event;

use eGen\MessageBus\Bus\EventBus;
use Model\Event\Commands\Event\CloseEvent;
use Model\Event\Repositories\IEventRepository;
use Model\Events\Events\EventWasClosed;

final class CloseEventHandler
{
    /** @var IEventRepository */
    private $events;

    /** @var EventBus */
    private $eventBus;

    public function __construct(IEventRepository $events, EventBus $eventBus)
    {
        $this->events   = $events;
        $this->eventBus = $eventBus;
    }

    public function __invoke(CloseEvent $command) : void
    {
        $event = $this->events->find($command->getEventId());
        $this->events->close($event);

        $this->eventBus->handle(
            new EventWasClosed(
                $event->getId(),
                $event->getUnitId(),
                $event->getDisplayName()
            )
        );
    }
}
