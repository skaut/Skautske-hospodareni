<?php

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\ReadModel\Queries\EventScopes;
use Nette\Caching\Cache;
use Skautis\Wsdl\WebServiceInterface;

final class EventScopesHandler
{

    private const CACHE_KEY = 'event_scopes';

    /** @var WebServiceInterface */
    private $eventWebservice;

    /** @var Cache */
    private $cache;

    public function __construct(WebServiceInterface $eventWebservice, Cache $cache)
    {
        $this->eventWebservice = $eventWebservice;
        $this->cache = $cache;
    }

    /**
     * @return array<int,string>
     */
    public function handle(EventScopes $query): array
    {
        // Scopes doesn't change so it's safe to cache them no matter what
        return $this->cache->load(self::CACHE_KEY, function() {
            $scopes = $this->eventWebservice->eventGeneralScopeAll();
            $scopePairs = [];

            foreach($scopes as $scope) {
                $scopePairs[$scope->ID] = $scope->DisplayName;
            }

            return $scopePairs;
        });
    }

}
