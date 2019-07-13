<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log\Sentry;

use Sentry\ClientBuilder;
use Sentry\State\Hub;
use Sentry\State\Scope;

final class HubFactory
{
    /**
     * @param callable[] $eventProcessors
     */
    public static function create(string $dsn, array $eventProcessors) : Hub
    {
        $client = ClientBuilder::create(['dsn' => $dsn])->getClient();

        $scope = new Scope();

        foreach ($eventProcessors as $processor) {
            $scope->addEventProcessor($processor);
        }

        return new Hub($client, $scope);
    }
}
