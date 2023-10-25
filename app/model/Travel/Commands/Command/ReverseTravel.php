<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Command;

/** @see ReverseTravelHandler */
final class ReverseTravel
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
