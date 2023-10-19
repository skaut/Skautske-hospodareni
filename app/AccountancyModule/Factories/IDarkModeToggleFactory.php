<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\Components\DarkModeToggle;

interface IDarkModeToggleFactory
{
    public function create(): DarkModeToggle;
}
