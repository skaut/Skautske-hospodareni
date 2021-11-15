<?php

declare(strict_types=1);

namespace Model\Logger\Subscribers;

use Model\Events\Events\EventWasClosed;
use Model\Events\Events\EventWasOpened;
use Model\Logger\Log\Type;
use Model\LoggerService;
use Model\UserService;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

final class EventSubscriber implements MessageSubscriberInterface
{
    private LoggerService $loggerService;

    private UserService $userService;

    public function __construct(LoggerService $logger, UserService $userService)
    {
        $this->loggerService = $logger;
        $this->userService   = $userService;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getHandledMessages(): array
    {
        return [
            EventWasOpened::class => ['method' => 'handleOpened'],
            EventWasClosed::class => ['method' => 'handleClosed'],
        ];
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function handleOpened(EventWasOpened $event): void
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

    public function handleClosed(EventWasClosed $event): void
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
