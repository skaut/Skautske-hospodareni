<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\SmtpAddForm;
use Model\Common\UnitId;

interface ISmtpAddFormFactory
{
    public function create(UnitId $unitId, int $userId) : SmtpAddForm;
}
