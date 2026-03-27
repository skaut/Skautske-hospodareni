<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\InvoiceCashPaymentDialog;

interface IInvoiceCashPaymentDialogFactory
{
    public function create(int $invoiceSequenceId): InvoiceCashPaymentDialog;
}
