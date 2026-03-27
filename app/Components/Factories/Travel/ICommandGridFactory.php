<?php

declare(strict_types=1);

namespace App\Components\Factories\Travel;

use App\Components\Travel\CommandGrid;

interface ICommandGridFactory
{
    public function create(int $unitId, int $userId): CommandGrid;
}
