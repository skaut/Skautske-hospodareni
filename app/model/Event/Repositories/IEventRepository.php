<?php

namespace Model\Event\Repositories;

use Model\Event\Event;

interface IEventRepository
{

    public function find(int $skautisId): Event;

    public function open(Event $event): void;

    public function close(Event $event): void;

}
