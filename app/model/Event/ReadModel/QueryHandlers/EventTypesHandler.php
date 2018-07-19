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

    /** @var WebServiceInterface */
    private $eventWebservice;

    /** @var Cache */
    private $cache;

    public function __construct(WebServiceInterface $eventWebservice, Cache $cache)
    {
        $this->eventWebservice = $eventWebservice;
        $this->cache           = $cache;
    }

    /**
     * @return string[]
     */
    public function handle(EventTypes $query) : array
    {
        // Event types don't change so it's safe to cache them no matter what
        return $this->cache->load(self::CACHE_KEY, function () {
            return Helpers::getPairs(
                $this->eventWebservice->eventGeneralTypeAll()
            );
        });
    }
}
