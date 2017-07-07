<?php

namespace App\Model\Subscribers;

use App\AccountancyModule\EventModule\Commands\EventWasClosed;
use App\AccountancyModule\EventModule\Commands\EventWasOpened;
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
            $e->getEvent()["ID_Unit"],
            $e->getUser()["ID"],
            "Uživatel '" . $e->getUser()["Person"] . "' otevřel akci '" . $e->getEvent()["DisplayName"] . "'.",
            $e->getEvent()["localId"]
        );
    }

    public function handleOpened(EventWasOpened $e): void
    {
        $this->loggerService->log(
            $e->getEvent()["ID_Unit"],
            $e->getUser()["ID"],
            "Uživatel '" . $e->getUser()["Person"] . "' otevřel akci '" . $e->getEvent()["DisplayName"] . "'.",
            $e->getEvent()["localId"]
        );
    }

}
