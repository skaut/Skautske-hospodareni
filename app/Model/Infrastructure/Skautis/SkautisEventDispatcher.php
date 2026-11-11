<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Skautis;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Rozesílá události knihovny skautIS (RequestPreEvent, RequestPostEvent, RequestFailEvent) posluchačům aplikace.
 *
 * Knihovna vyžaduje PSR-14 dispatcher a v produkčních závislostech žádný není; posluchač si události třídí sám.
 */
final class SkautisEventDispatcher implements EventDispatcherInterface
{
    /** @var list<callable(object): void> */
    private array $listeners = [];

    /** @param callable(object): void $listener */
    public function addListener(callable $listener): void
    {
        $this->listeners[] = $listener;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listeners as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
