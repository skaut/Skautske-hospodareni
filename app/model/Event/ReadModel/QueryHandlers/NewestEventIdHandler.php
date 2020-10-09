<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\ReadModel\Queries\NewestEventId;
use Model\Event\Repositories\IEventRepository;

final class NewestEventIdHandler
{
    private IEventRepository $events;

    public function __construct(IEventRepository $events)
    {
        $this->events = $events;
    }

    public function __invoke(NewestEventId $query) : ?int
    {
        return $this->events->getNewestEventId();
    }
}
