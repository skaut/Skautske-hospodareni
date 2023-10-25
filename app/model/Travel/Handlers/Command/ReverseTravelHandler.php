<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Command;

use Model\Travel\Commands\Command\ReverseTravel;
use Model\Travel\Repositories\ICommandRepository;

final class ReverseTravelHandler
{
    public function __construct(private ICommandRepository $commands)
    {
    }

    public function __invoke(ReverseTravel $command): void
    {
        $travelCommand = $this->commands->find($command->getCommandId());

        $travelCommand->reverseTravel($command->getTravelId());

        $this->commands->save($travelCommand);
    }
}
