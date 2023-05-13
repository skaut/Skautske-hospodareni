<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\PaymentNoteDialog;

interface IPaymentNoteDialogFactory
{
    public function create(int $groupId): PaymentNoteDialog;
}
