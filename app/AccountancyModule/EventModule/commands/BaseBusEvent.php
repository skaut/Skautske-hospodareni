<?php

namespace App\AccountancyModule\EventModule\Commands;

class BaseBusEvent
{
    /** @var array Skautis detail of event */
    protected $event;

    /** @var array Skautis user detail */
    protected $user;

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
