<?php

declare(strict_types=1);

namespace App\Model\Travel\Commands\Command;

use App\Model\Travel\Handlers\Command\DuplicateTravelHandler;

/** @see DuplicateTravelHandler */
final class DuplicateTravel
{
    public function __construct(private int $commandId, private int $travelId)
    {
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
