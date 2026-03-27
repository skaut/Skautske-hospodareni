<?php

declare(strict_types=1);

namespace App\Model\Event\Handlers\Event;

use App\Model\Common\Services\EventBus;
use App\Model\Event\Commands\CancelEvent;
use App\Model\Events\Events\EventWasCanceled;
use Skautis\Skautis;

final class CancelEventHandler
{
    public function __construct(private Skautis $skautis, private EventBus $eventBus)
    {
    }

    public function __invoke(CancelEvent $command): void
    {
        $this->skautis->event->EventGeneralUpdateCancel([
            'ID' => $command->getEventId()->toInt(),
            'CancelDecision' => ' ',
        ], 'eventGeneral');

        $this->eventBus->handle(new EventWasCanceled($command->getEventId()));
    }
}
