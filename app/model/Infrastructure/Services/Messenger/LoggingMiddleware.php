<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger, private string $level)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->logger->log($this->level, 'Started handling a message', ['message' => $envelope]);

        $result = $stack->next()->handle($envelope, $stack);

        $this->logger->log($this->level, 'Finished handling a message', ['message' => $envelope]);

        return $result;
    }
}
