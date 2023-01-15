<?php

declare(strict_types=1);

namespace Model\Common\Services;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBus
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    public function handle(object $event): void
    {
        try {
            $this->eventBus->dispatch($event);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }
}
