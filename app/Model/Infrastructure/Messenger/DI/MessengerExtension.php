<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Messenger\DI;

use App\Model\Infrastructure\Messenger\HandlerDefinition;
use App\Model\Infrastructure\Messenger\LazyHandlersLocator;
use LogicException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

use function array_keys;
use function array_map;
use function assert;
use function class_exists;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Minimální Nette DI integrace symfony/messenger – nahrazuje neudržovaný fmasa/messenger.
 *
 * Podporuje jen to, co aplikace používá: synchronní sběrnice (command/event/query bus) s handlery
 * registrovanými tagem `messenger.messageHandler` (s klíčem `bus`) a middleware. Žádné asynchronní
 * transporty, routing ani serializace – to symfony/messenger umí, ale zde to nepotřebujeme.
 */
final class MessengerExtension extends CompilerExtension
{
    private const TagHandler = 'messenger.messageHandler';

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'buses' => Expect::arrayOf(Expect::from(new BusConfig())),
        ]);
    }

    /** @return array<string, BusConfig> */
    private function getBuses(): array
    {
        /** @var object{buses: array<string, BusConfig>} $config */
        $config = $this->getConfig();

        return $config->buses;
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        foreach ($this->getBuses() as $busName => $busConfig) {
            $middleware = [];

            foreach ($busConfig->middleware as $index => $definition) {
                $middleware[] = $builder->addDefinition($this->prefix($busName.'.middleware.'.$index))
                    ->setFactory($definition);
            }

            $handlersLocator = $builder->addDefinition($this->prefix($busName.'.handlersLocator'))
                ->setFactory(LazyHandlersLocator::class);

            $middleware[] = $builder->addDefinition($this->prefix($busName.'.handleMiddleware'))
                ->setFactory(HandleMessageMiddleware::class, [$handlersLocator, $busConfig->allowNoHandlers]);

            $builder->addDefinition($this->prefix($busName.'.bus'))
                ->setFactory(MessageBus::class, [$middleware]);
        }
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();
        $handlersByBus = $this->discoverHandlers();

        foreach ($this->getBuses() as $busName => $busConfig) {
            $handlers = $handlersByBus[$busName] ?? [];

            if ($busConfig->singleHandlerPerMessage) {
                foreach ($handlers as $messageName => $definitions) {
                    if (count($definitions) > 1) {
                        throw new LogicException(sprintf('Message "%s" is handled by multiple handlers on single-handler bus "%s": %s', $messageName, $busName, implode(', ', array_keys($definitions))));
                    }
                }
            }

            $locator = $builder->getDefinition($this->prefix($busName.'.handlersLocator'));
            assert($locator instanceof ServiceDefinition);
            $locator->setArguments([array_map('array_values', $handlers)]);
        }
    }

    /**
     * @return array<string, array<string, array<string, HandlerDefinition>>> bus => message => serviceName => definition
     */
    private function discoverHandlers(): array
    {
        $builder = $this->getContainerBuilder();
        $result = [];

        foreach ($builder->findByTag(self::TagHandler) as $serviceName => $tag) {
            $definition = $builder->getDefinition($serviceName);
            $className = $definition->getType();
            assert($className !== null && class_exists($className));

            $tag = is_array($tag) ? $tag : [];
            $defaultBus = $tag['bus'] ?? null;

            foreach ($this->resolveHandledMessages($className, $tag) as $message => $options) {
                $bus = $options['bus'] ?? $defaultBus;
                $method = $options['method'] ?? '__invoke';
                assert(is_string($bus), sprintf('Handler "%s" is missing target bus.', $serviceName));

                unset($options['bus'], $options['method']);
                $result[$bus][$message][$serviceName] = new HandlerDefinition($serviceName, $method, $options);
            }
        }

        return $result;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $tag
     *
     * @return array<string, array<string, mixed>> message class => options
     */
    private function resolveHandledMessages(string $className, array $tag): array
    {
        if (isset($tag['handles']) && is_string($tag['handles'])) {
            return [$tag['handles'] => ['method' => $tag['method'] ?? '__invoke']];
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->implementsInterface(MessageSubscriberInterface::class)) {
            return $this->normalizeHandledMessages($className::getHandledMessages());
        }

        return [$this->guessMessageFromInvoke($reflection) => ['method' => '__invoke']];
    }

    /**
     * @param iterable<int|string, mixed> $handledMessages
     *
     * @return array<string, array<string, mixed>>
     */
    private function normalizeHandledMessages(iterable $handledMessages): array
    {
        $normalized = [];

        foreach ($handledMessages as $message => $options) {
            if (is_int($message)) {
                assert(is_string($options));
                $normalized[$options] = [];

                continue;
            }

            if (is_string($options)) {
                $options = ['method' => $options];
            }

            assert(is_array($options));
            $normalized[$message] = $options;
        }

        return $normalized;
    }

    private function guessMessageFromInvoke(ReflectionClass $reflection): string
    {
        $method = $reflection->getMethod('__invoke');
        $parameters = $method->getParameters();

        if (count($parameters) !== 1) {
            throw new LogicException(sprintf('Handler "%s::__invoke()" must take exactly one parameter.', $reflection->getName()));
        }

        $type = $parameters[0]->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new LogicException(sprintf('Handler "%s::__invoke()" must type-hint the handled message class.', $reflection->getName()));
        }

        return $type->getName();
    }
}
