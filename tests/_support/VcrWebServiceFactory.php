<?php

declare(strict_types=1);

namespace Hskauting\Tests;

use Psr\EventDispatcher\EventDispatcherInterface;
use Skaut\Skautis\InvalidArgumentException;
use Skaut\Skautis\Wsdl\WebService;
use Skaut\Skautis\Wsdl\WebServiceFactoryInterface;
use Skaut\Skautis\Wsdl\WebServiceInterface;
use VCR\Util\SoapClient;

/**
 * Creates skautIS web services whose SOAP requests php-vcr replays from cassettes.
 */
final class VcrWebServiceFactory implements WebServiceFactoryInterface
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    /** @param array<string, mixed> $options */
    public function createWebService(string $url, array $options): WebServiceInterface
    {
        return new WebService(new SoapClient($url, $options), $options, $this->eventDispatcher);
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        if ($this->eventDispatcher !== null) {
            throw new InvalidArgumentException('Event dispatcher is already set.');
        }

        $this->eventDispatcher = $eventDispatcher;
    }
}
