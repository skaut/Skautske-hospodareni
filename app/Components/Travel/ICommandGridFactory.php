<?php

declare(strict_types=1);

namespace App\Components\Travel;

interface ICommandGridFactory
{
    public function create(int $unitId, int $userId): CommandGrid;
}
