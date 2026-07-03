<?php

declare(strict_types=1);

namespace App\Model\Travel\Handlers\Command;

use App\Model\Travel\Commands\Command\DuplicateTravel;
use App\Model\Travel\Repositories\ICommandRepository;

final class DuplicateTravelHandler
{
    public function __construct(private ICommandRepository $commands)
    {
    }

    public function __invoke(DuplicateTravel $command): void
    {
        $travelCommand = $this->commands->find($command->getCommandId());

        $travelCommand->duplicateTravel($command->getTravelId());

        $this->commands->save($travelCommand);
    }
}
