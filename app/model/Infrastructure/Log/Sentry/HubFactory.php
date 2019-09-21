<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log\Sentry;

use Sentry\ClientBuilder;
use Sentry\Integration\ModulesIntegration;
use Sentry\Integration\RequestIntegration;
use Sentry\Options;
use Sentry\State\Hub;
use Sentry\State\Scope;

final class HubFactory
{
    /**
     * @param callable[] $eventProcessors
     */
    public static function create(?string $dsn, array $eventProcessors, string $releaseHash) : Hub
    {
        $scope = new Scope();

        foreach ($eventProcessors as $processor) {
            $scope->addEventProcessor($processor);
        }

        $options = new Options(['dsn' => $dsn]);
        $options->setRelease($releaseHash);
        $options->setIntegrations([new RequestIntegration($options), new ModulesIntegration()]);

        return new Hub((new ClientBuilder($options))->getClient(), $scope);
    }
}
