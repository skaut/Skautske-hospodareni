<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\MassAddForm;

interface IMassAddFormFactory
{
    public function create(int $groupId): MassAddForm;
}
