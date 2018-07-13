<?php

declare(strict_types=1);

use eGen\MessageBus\Bus\CommandBus;

abstract class CommandHandlerTest extends IntegrationTest
{
    /** @var CommandBus */
    protected $commandBus;

    protected function _before() : void
    {
        parent::_before();
        $this->commandBus = $this->tester->grabService(CommandBus::class);
    }
}
