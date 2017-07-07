<?php

namespace App\AccountancyModule\EventModule\Commands;

class BaseChit extends BaseBusEvent
{
    /** @var array */
    protected $chit;

    public function __construct(array $event, \stdClass $user, array $chit)
    {
        parent::__construct($event, $user);
        $this->chit = $chit;
    }

    public function getChit(): array
    {
        return $this->chit;
    }
}
