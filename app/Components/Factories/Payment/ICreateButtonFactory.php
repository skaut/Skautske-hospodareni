<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\CreateButton;

interface ICreateButtonFactory
{
    public function create(): CreateButton;
}
