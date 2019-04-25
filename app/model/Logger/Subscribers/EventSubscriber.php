<?php

declare(strict_types=1);

namespace Model\Logger\Subscribers;

use Model\Events\Events\EventWasClosed;
use Model\Events\Events\EventWasOpened;
use Model\Logger\Log\Type;
use Model\LoggerService;
use Model\UserService;

class EventSubscriber
{
    /** @var LoggerService */
    private $loggerService;

    /** @var UserService */
    private $userService;

    public function __construct(LoggerService $logger, UserService $userService)
    {
        $this->loggerService = $logger;
        $this->userService   = $userService;
    }

    public function handleOpened(EventWasOpened $event) : void
    {
        $user = $this->userService->getUserDetail();
        $this->loggerService->log(
            $event->getUnitId(),
            $user->ID,
            "Uživatel '" . $user->Person . "' otevřel akci '" . $event->getEventName() . "' (" . $event->getEventId() . ').',
            Type::get(Type::OBJECT),
            $event->getEventId()->toInt()
        );
    }

    public function handleClosed(EventWasClosed $event) : void
    {
        $user = $this->userService->getUserDetail();

        $this->loggerService->log(
            $event->getUnitId(),
            $user->ID,
            "Uživatel '" . $user->Person . "' uzavřel akci '" . $event->getEventName() . "' (" . $event->getEventId() . ').',
            Type::get(Type::OBJECT),
            $event->getEventId()->toInt()
        );
    }
}
