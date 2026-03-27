<?php

declare(strict_types=1);

namespace App\Components\Factories;

use App\Components\LoginPanel;

interface ILoginPanelFactory
{
    public function create(): LoginPanel;
}
