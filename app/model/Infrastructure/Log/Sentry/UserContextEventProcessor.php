<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log;

use Sentry\Event;
use Sentry\UserDataBag;

final class UserContextEventProcessor
{
    public function __construct(private UserContextProvider $userContext)
    {
    }

    public function __invoke(Event $event): Event
    {
        $userData = $this->userContext->getUserData();

        if ($userData !== null) {
            $event->setUser(UserDataBag::createFromArray($userData));
        }

        return $event;
    }
}
