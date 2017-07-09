<?php

namespace App\Model\Subscribers;

use App\AccountancyModule\EventModule\Commands\EventWasClosed;
use App\AccountancyModule\EventModule\Commands\EventWasOpened;
use Model\Logger\Log\Type;
use Model\LoggerService;

class EventListener
{
    private $loggerService;

    public function __construct(LoggerService $ls)
    {
        $this->loggerService = $ls;
    }

    public function handleClosed(EventWasClosed $e): void
    {
        $this->loggerService->log(
            $e->getUnitId(),
            $e->getUserId(),
            "Uživatel '" . $e->getUserName() . "' uzavřel akci '" . $e->getEventName() . "'.",
            Type::get(Type::OBJECT),
            $e->getLocalId()
        );
    }

    public function handleOpened(EventWasOpened $e): void
    {
        $this->loggerService->log(
            $e->getUnitId(),
            $e->getUserId(),
            "Uživatel '" . $e->getUserName() . "' otevřel akci '" . $e->getEventName() . "'.",
            Type::get(Type::OBJECT),
            $e->getLocalId()
        );
    }

}
