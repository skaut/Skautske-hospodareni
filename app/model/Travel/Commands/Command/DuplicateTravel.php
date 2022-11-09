<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Command;

use Model\Travel\Handlers\Command\DuplicateTravelHandler;

/** @see DuplicateTravelHandler */
final class DuplicateTravel
{
    private int $commandId;

    private int $travelId;

    public function __construct(int $commandId, int $travelId)
    {
        $this->commandId = $commandId;
        $this->travelId  = $travelId;
    }

    public function getCommandId(): int
    {
        return $this->commandId;
    }

    public function getTravelId(): int
    {
        return $this->travelId;
    }
}
