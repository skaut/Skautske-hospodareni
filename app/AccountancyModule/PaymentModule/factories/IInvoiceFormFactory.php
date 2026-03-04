<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\InvoiceForm;
use Entity\InvoiceSequence;

interface IInvoiceFormFactory
{
    public function create(InvoiceSequence $invoiceSequence): InvoiceForm;
}
