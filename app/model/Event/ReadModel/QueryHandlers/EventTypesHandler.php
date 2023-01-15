<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\ReadModel\Helpers;
use Model\Event\ReadModel\Queries\EventTypes;
use Nette\Caching\Cache;
use Skautis\Wsdl\WebServiceInterface;

final class EventTypesHandler
{
    private const CACHE_KEY = 'event_types';

    public function __construct(private WebServiceInterface $eventWebservice, private Cache $cache)
    {
    }

    /** @return string[] */
    public function __invoke(EventTypes $query): array
    {
        // Event types don't change so it's safe to cache them no matter what
        return $this->cache->load(self::CACHE_KEY, function () {
            return Helpers::getPairs(
                $this->eventWebservice->eventGeneralTypeAll(),
            );
        });
    }
}
