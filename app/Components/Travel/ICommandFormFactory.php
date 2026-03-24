<?php

declare(strict_types=1);

namespace App\Components\Travel;

interface ICommandFormFactory
{
    public function create(int $unitId, ?int $commandId): CommandForm;
}
