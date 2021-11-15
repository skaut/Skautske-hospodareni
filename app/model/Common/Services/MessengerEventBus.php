<?php

declare(strict_types=1);

namespace Model\Common\Services;

use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBus
{
    private MessageBusInterface $eventBus;

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function handle(object $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
