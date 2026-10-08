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
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

use function array_keys;
use function array_map;
use function assert;
use function class_exists;
use function count;
use function implode;
use function is_array;
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
        $fromAttributes = $this->resolveFromAttributes($reflection);

        if ($fromAttributes !== []) {
            return $fromAttributes;
        }

        return [$this->guessMessageFromMethod($reflection->getMethod('__invoke')) => ['method' => '__invoke']];
    }

    /**
     * Handlery označené `#[AsMessageHandler]`.
     *
     * Atribut nahradil zrušené `MessageSubscriberInterface`; zpracovávaná zpráva se bere z typu
     * parametru metody, takže se nikde neduplikuje. Jedna třída může mít označených metod víc,
     * každou pro jinou zprávu.
     *
     * @return array<string, array<string, mixed>> message class => options
     */
    private function resolveFromAttributes(ReflectionClass $reflection): array
    {
        $handled = [];

        foreach ($reflection->getAttributes(AsMessageHandler::class) as $attribute) {
            $instance = $attribute->newInstance();
            $method = $instance->method ?? '__invoke';

            $handled[$instance->handles ?? $this->guessMessageFromMethod($reflection->getMethod($method))] = [
                'method' => $method,
                'bus' => $instance->bus,
            ];
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(AsMessageHandler::class) as $attribute) {
                $instance = $attribute->newInstance();

                $handled[$instance->handles ?? $this->guessMessageFromMethod($method)] = [
                    'method' => $method->getName(),
                    'bus' => $instance->bus,
                ];
            }
        }

        return $handled;
    }

    private function guessMessageFromMethod(ReflectionMethod $method): string
    {
        $parameters = $method->getParameters();
        $name = $method->getDeclaringClass()->getName().'::'.$method->getName().'()';

        if (count($parameters) !== 1) {
            throw new LogicException(sprintf('Handler "%s" must take exactly one parameter.', $name));
        }

        $type = $parameters[0]->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new LogicException(sprintf('Handler "%s" must type-hint the handled message class.', $name));
        }

        return $type->getName();
    }
}
