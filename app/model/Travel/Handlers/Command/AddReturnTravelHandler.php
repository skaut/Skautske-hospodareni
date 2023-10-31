<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Command;

use Model\Travel\Commands\Command\AddReturnTravel;
use Model\Travel\Repositories\ICommandRepository;

final class AddReturnTravelHandler
{
    public function __construct(private ICommandRepository $commands)
    {
    }

    public function __invoke(AddReturnTravel $command): void
    {
        $travelCommand = $this->commands->find($command->getCommandId());

        $travelCommand->addReturnTravel($command->getTravelId());

        $this->commands->save($travelCommand);
    }
}
