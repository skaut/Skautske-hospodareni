<?php

declare(strict_types=1);

namespace Model\Event\Repositories;

use Model\Event\Event;
use Model\Event\EventNotFoundException;

interface IEventRepository
{
    /**
     * @throws EventNotFoundException
     */
    public function find(int $skautisId) : Event;

    public function open(Event $event) : void;

    public function close(Event $event) : void;

    public function update(Event $event) : void;

    public function getNewestEventId() : ?int;
}
