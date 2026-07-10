<?php

declare(strict_types=1);

namespace App\Components\Factories\Travel;

use App\Components\Travel\CommandGrid;

interface ICommandGridFactory
{
    /** @param int[] $readableUnitIds */
    public function create(array $readableUnitIds, int $userId): CommandGrid;
}
