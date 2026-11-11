<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Services;

use App\Model\Infrastructure\Skautis\SkautisEventDispatcher;
use Psr\Log\LoggerInterface;
use Skaut\Skautis\Wsdl\Event\RequestFailEvent;
use Skaut\Skautis\Wsdl\Event\RequestPostEvent;

use function sprintf;

final class SkautisCallListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function register(SkautisEventDispatcher $dispatcher): void
    {
        $dispatcher->addListener($this->handle(...));
    }

    public function handle(object $event): void
    {
        if ($event instanceof RequestPostEvent) {
            $this->logger->debug(
                sprintf('Skautis query "%s" sucessfuly performed.', $event->getFname()),
                ['arguments' => $event->getArgs(), 'time' => $event->getDuration(), 'result' => $event->getResult()],
            );
        } elseif ($event instanceof RequestFailEvent) {
            $this->logger->debug(
                sprintf('Skautis query "%s" returns error: %s', $event->getFname(), $event->getExceptionString()),
                ['arguments' => $event->getArgs(), 'time' => $event->getDuration()],
            );
        }
    }
}
