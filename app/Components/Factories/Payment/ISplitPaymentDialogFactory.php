<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\SplitPaymentDialog;

interface ISplitPaymentDialogFactory
{
    public function create(int $groupId): SplitPaymentDialog;
}
