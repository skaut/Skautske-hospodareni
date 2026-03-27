<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\PairButton;

interface IPairButtonFactory
{
    public function create(): PairButton;
}
