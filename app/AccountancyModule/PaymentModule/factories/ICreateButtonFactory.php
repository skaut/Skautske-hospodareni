<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\CreateButton;

interface ICreateButtonFactory
{
    public function create(): CreateButton;
}
