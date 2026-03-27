<?php

declare(strict_types=1);

namespace App\Model\Event\Handlers\Event;

use App\Model\Common\Services\EventBus;
use App\Model\Event\Commands\Event\CloseEvent;
use App\Model\Event\Repositories\IEventRepository;
use App\Model\Events\Events\EventWasClosed;

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
