<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Factories;

use App\AccountancyModule\TravelModule\Components\CommandGrid;

interface ICommandGridFactory
{
    public function create(int $unitId) : CommandGrid;
}
