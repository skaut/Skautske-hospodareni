<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\ReadModel\Helpers;
use Model\Event\ReadModel\Queries\EventStates;
use Nette\Caching\Cache;
use Skautis\Wsdl\WebServiceInterface;

final class EventStatesHandler
{
    private const CACHE_KEY = 'event_states';

    private WebServiceInterface $eventWebservice;

    private Cache $cache;

    public function __construct(WebServiceInterface $eventWebservice, Cache $cache)
    {
        $this->eventWebservice = $eventWebservice;
        $this->cache           = $cache;
    }

    /**
     * @return string[]
     */
    public function __invoke(EventStates $query) : array
    {
        return $this->cache->load(self::CACHE_KEY, function () {
            return Helpers::getPairs(
                $this->eventWebservice->eventGeneralStateAll()
            );
        });
    }
}
