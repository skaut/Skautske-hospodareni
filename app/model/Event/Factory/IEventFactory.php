<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Event\Event;

interface IEventFactory
{
    public function create(\stdClass $skautisEvent) : Event;
}
