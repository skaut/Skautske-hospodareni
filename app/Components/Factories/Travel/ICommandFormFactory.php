<?php

declare(strict_types=1);

namespace App\Components\Factories\Travel;

use App\Components\Travel\CommandForm;

interface ICommandFormFactory
{
    public function create(int $unitId, ?int $commandId): CommandForm;
}
