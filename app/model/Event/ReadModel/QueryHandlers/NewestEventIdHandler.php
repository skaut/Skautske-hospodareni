<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\ReadModel\Queries\NewestEventId;
use Model\Event\Repositories\IEventRepository;

final class NewestEventIdHandler
{
    public function __construct(private IEventRepository $events)
    {
    }

    public function __invoke(NewestEventId $query): int|null
    {
        return $this->events->getNewestEventId();
    }
}
