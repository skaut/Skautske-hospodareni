<?php

declare(strict_types=1);

namespace Model\Event\Repositories;

use Model\Event\Event;
use Model\Event\EventNotFound;
use Model\Event\SkautisEventId;

interface IEventRepository
{
    /**
     * @throws EventNotFound
     */
    public function find(SkautisEventId $id) : Event;

    public function open(Event $event) : void;

    public function close(Event $event) : void;

    public function update(Event $event) : void;

    public function getNewestEventId() : ?int;
}
