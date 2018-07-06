<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Event;

use eGen\MessageBus\Bus\EventBus;
use Model\Event\Commands\CancelEvent;
use Model\Events\Events\EventWasCanceled;
use Skautis\Skautis;

final class CancelEventHandler
{
    /** @var Skautis */
    private $skautis;

    /** @var EventBus */
    private $eventBus;

    public function __construct(Skautis $skautis, EventBus $eventBus)
    {
        $this->skautis  = $skautis;
        $this->eventBus = $eventBus;
    }

    public function handle(CancelEvent $command) : void
    {
        $this->skautis->event->EventGeneralUpdateCancel([
            'ID' => $command->getEventId()->toInt(),
            'CancelDecision' => ' ',
        ], 'eventGeneral');

        $this->eventBus->handle(new EventWasCanceled($command->getEventId()));
    }
}
