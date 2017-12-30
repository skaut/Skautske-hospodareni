<?php

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\ReadModel\Queries\NewestEventId;
use Model\Event\Repositories\IEventRepository;

final class NewestEventIdHandler
{

    /** @var IEventRepository */
    private $events;

    public function __construct(IEventRepository $events)
    {
        $this->events = $events;
    }

    public function handle(NewestEventId $query): ?int
    {
        return $this->events->getNewestEventId();
    }

}
