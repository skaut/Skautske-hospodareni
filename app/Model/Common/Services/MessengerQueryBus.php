<?php

declare(strict_types=1);

namespace App\Model\Common\Services;

use LogicException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerQueryBus implements QueryBus
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    public function handle(object $query): mixed
    {
        try {
            $stamp = $this->queryBus->dispatch($query)->last(HandledStamp::class);
            if (! $stamp instanceof HandledStamp) {
                throw new LogicException('Assertion failed.');
            }
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious() ?? $e;
        }

        return $stamp->getResult();
    }
}
