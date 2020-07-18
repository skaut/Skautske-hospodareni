<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log;

use Sentry\Event;

final class UserContextEventProcessor
{
    private UserContextProvider $userContext;

    public function __construct(UserContextProvider $userContext)
    {
        $this->userContext = $userContext;
    }

    public function __invoke(Event $event) : Event
    {
        $userData = $this->userContext->getUserData();

        if ($this->userContext->getUserData() !== null) {
            $event->getUserContext()->setData($userData);
        }

        return $event;
    }
}
