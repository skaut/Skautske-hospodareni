<?php

declare(strict_types=1);

namespace Model\Common\Services;

use eGen\MessageBus\Bus\EventBus as InnerEventBus;

final class EgenEventBus implements EventBus
{
    private InnerEventBus $eventBus;

    public function __construct(InnerEventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function handle(object $event): void
    {
        $this->eventBus->handle($event);
    }
}
