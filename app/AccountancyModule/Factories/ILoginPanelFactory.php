<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\Components\LoginPanel;

interface ILoginPanelFactory
{
    public function create() : LoginPanel;
}
