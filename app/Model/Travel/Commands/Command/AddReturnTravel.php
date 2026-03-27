<?php

declare(strict_types=1);

namespace App\Model\Travel\Commands\Command;

use App\Model\Travel\Handlers\Command\AddReturnTravelHandler;

/** @see AddReturnTravelHandler */
final class AddReturnTravel
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
