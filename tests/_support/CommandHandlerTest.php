<?php

declare(strict_types=1);

use eGen\MessageBus\Bus\CommandBus;

abstract class CommandHandlerTest extends IntegrationTest
{
    protected CommandBus $commandBus;

    protected function _before() : void
    {
        parent::_before();
        $this->commandBus = $this->tester->grabService(CommandBus::class);
    }
}
