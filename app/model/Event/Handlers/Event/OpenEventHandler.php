<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Event;

use Model\Common\Services\EventBus;
use Model\Event\Commands\Event\OpenEvent;
use Model\Event\Repositories\IEventRepository;
use Model\Events\Events\EventWasOpened;

final class OpenEventHandler
{
    public function __construct(private IEventRepository $events, private EventBus $eventBus)
    {
    }

    public function __invoke(OpenEvent $command): void
    {
        $event = $this->events->find($command->getEventId());

        $this->events->open($event);

        $this->eventBus->handle(
            new EventWasOpened(
                $event->getId(),
                $event->getUnitId()->toInt(),
                $event->getDisplayName(),
            ),
        );
    }
}
