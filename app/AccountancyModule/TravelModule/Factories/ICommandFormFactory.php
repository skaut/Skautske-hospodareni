<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Factories;

use App\AccountancyModule\TravelModule\Components\CommandForm;

interface ICommandFormFactory
{
    public function create(int $unitId, ?int $commandId) : CommandForm;
}
