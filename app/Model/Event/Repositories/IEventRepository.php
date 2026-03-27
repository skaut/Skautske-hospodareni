<?php

declare(strict_types=1);

namespace App\Model\Event\Repositories;

use App\Model\Event\Event;
use App\Model\Event\EventNotFound;
use App\Model\Event\SkautisEventId;

interface IEventRepository
{
    /** @throws EventNotFound */
    public function find(SkautisEventId $id): Event;

    public function open(Event $event): void;

    public function close(Event $event): void;

    public function update(Event $event): void;

    public function getNewestEventId(): ?int;
}
