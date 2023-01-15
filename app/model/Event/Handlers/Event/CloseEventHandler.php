<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Event;

use Model\Common\Services\EventBus;
use Model\Event\Commands\Event\CloseEvent;
use Model\Event\Repositories\IEventRepository;
use Model\Events\Events\EventWasClosed;

final class CloseEventHandler
{
    public function __construct(private IEventRepository $events, private EventBus $eventBus)
    {
    }

    public function __invoke(CloseEvent $command): void
    {
        $event = $this->events->find($command->getEventId());
        $this->events->close($event);

        $this->eventBus->handle(
            new EventWasClosed(
                $event->getId(),
                $event->getUnitId()->toInt(),
                $event->getDisplayName(),
            ),
        );
    }
}
