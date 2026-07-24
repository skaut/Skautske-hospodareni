<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Messenger;

use Nette\DI\Container;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

use function assert;
use function is_callable;

/**
 * Mapuje třídu zprávy na handlery zaregistrované v DI. Služby se z kontejneru získávají líně
 * (až při dispatchi), aby se předešlo cyklickému sestavování kontejneru při kompilaci.
 */
final class LazyHandlersLocator implements HandlersLocatorInterface
{
    /** @param array<string, list<HandlerDefinition>> $handlerDefinitions */
    public function __construct(
        private readonly array $handlerDefinitions,
        private readonly Container $container,
    ) {
    }

    /** @return list<HandlerDescriptor> */
    public function getHandlers(Envelope $envelope): iterable
    {
        $handlers = [];

        foreach ($this->handlerDefinitions[$envelope->getMessage()::class] ?? [] as $definition) {
            $handler = [$this->container->getService($definition->serviceName), $definition->methodName];
            assert(is_callable($handler));

            $options = $definition->options;
            $options['alias'] ??= $definition->serviceName;

            $handlers[] = new HandlerDescriptor($handler, $options);
        }

        return $handlers;
    }
}
