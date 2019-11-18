<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\SmtpUpdateForm;
use Model\Common\UnitId;

interface ISmtpUpdateFormFactory
{
    public function create(UnitId $unitId) : SmtpUpdateForm;
}
