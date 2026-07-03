<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\ReadModel\Queries\NewestEventId;
use App\Model\Event\Repositories\IEventRepository;

final class NewestEventIdHandler
{
    public function __construct(private IEventRepository $events)
    {
    }

    public function __invoke(NewestEventId $query): ?int
    {
        return $this->events->getNewestEventId();
    }
}
