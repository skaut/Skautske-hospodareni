<?php

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\PairButton;

interface IPairButtonFactory
{

    public function create() : PairButton;

}
