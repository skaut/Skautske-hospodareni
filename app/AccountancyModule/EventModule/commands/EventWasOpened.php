<?php

namespace App\AccountancyModule\EventModule\Commands;

class EventWasOpened
{
    /** @var array Skautis detail of event */
    private $event;

    /** @var stdClass Skautis user detail */
    private $user;

    public function __construct(array $event, \stdClass $user)
    {
        $this->event = $event;
        $this->user = (array)$user;
    }

    public function getEvent(): array
    {
        return $this->event;
    }

    public function getUser(): array
    {
        return $this->user;
    }

}