<?php

declare(strict_types=1);

namespace Model\Common\Services;

use eGen\MessageBus\Bus\CommandBus as InnerCommandBus;

final class EgenCommandBus implements CommandBus
{
    private InnerCommandBus $commandBus;

    public function __construct(InnerCommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(object $command): void
    {
        $this->commandBus->handle($command);
    }
}
