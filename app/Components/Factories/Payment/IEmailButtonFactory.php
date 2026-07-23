<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\EmailButton;
use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\Payment;

interface IEmailButtonFactory
{
    /** @param Payment[] $payments */
    public function create(bool $isEditable, array $payments, Group $group): EmailButton;
}
