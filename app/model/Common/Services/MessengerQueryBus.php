<?php

declare(strict_types=1);

namespace Model\Common\Services;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

use function assert;

final class MessengerQueryBus implements QueryBus
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    public function handle(object $query): mixed
    {
        try {
            $stamp = $this->queryBus->dispatch($query)->last(HandledStamp::class);
            assert($stamp instanceof HandledStamp);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }

        return $stamp->getResult();
    }
}
