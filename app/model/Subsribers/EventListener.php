<?php

namespace App\Model\Subscribers;

use Model\Events\Events\EventWasClosed;
use Model\Events\Events\EventWasOpened;
use Model\Logger\Log\Type;
use Model\LoggerService;

class EventListener
{
    private $loggerService;

    public function __construct(LoggerService $logger)
    {
        $this->loggerService = $logger;
    }

    public function handleClosed(EventWasClosed $event): void
    {
        $this->loggerService->log(
            $event->getUnitId(),
            $event->getUserId(),
            "Uživatel '" . $event->getUserName() . "' uzavřel akci '" . $event->getEventName() . "' (" . $event->getEventId() . ").",
            Type::get(Type::OBJECT),
            $event->getLocalId()
        );
    }

    public function handleOpened(EventWasOpened $event): void
    {
        $this->loggerService->log(
            $event->getUnitId(),
            $event->getUserId(),
            "Uživatel '" . $event->getUserName() . "' otevřel akci '" . $event->getEventName() . "' (" . $event->getEventId() . ").",
            Type::get(Type::OBJECT),
            $event->getLocalId()
        );
    }

}
