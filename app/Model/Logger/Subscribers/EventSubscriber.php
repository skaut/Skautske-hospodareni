<?php

declare(strict_types=1);

namespace App\Model\Logger\Subscribers;

use App\Model\Events\Events\EventWasClosed;
use App\Model\Events\Events\EventWasOpened;
use App\Model\Logger\Log\Type;
use App\Model\Logger\LoggerService;
use App\Model\User\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class EventSubscriber
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $logger, private UserService $userService)
    {
        $this->loggerService = $logger;
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    #[AsMessageHandler]
    public function handleOpened(EventWasOpened $event): void
    {
        $user = $this->userService->getUserDetail();
        $this->loggerService->log(
            $event->getUnitId(),
            $user->ID,
            "Uživatel '".$user->Person."' otevřel akci '".$event->getEventName()."' (".$event->getEventId().').',
            Type::get(Type::OBJECT),
            $event->getEventId()->toInt(),
        );
    }

    #[AsMessageHandler]
    public function handleClosed(EventWasClosed $event): void
    {
        $user = $this->userService->getUserDetail();

        $this->loggerService->log(
            $event->getUnitId(),
            $user->ID,
            "Uživatel '".$user->Person."' uzavřel akci '".$event->getEventName()."' (".$event->getEventId().').',
            Type::get(Type::OBJECT),
            $event->getEventId()->toInt(),
        );
    }
}
