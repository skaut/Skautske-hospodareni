<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\PaymentNoteDialog;

interface IPaymentNoteDialogFactory
{
    public function create(int $groupId): PaymentNoteDialog;
}
