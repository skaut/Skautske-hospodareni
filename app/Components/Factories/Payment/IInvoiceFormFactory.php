<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\InvoiceForm;

use App\Model\Invoice\Entity\InvoiceSequence;

interface IInvoiceFormFactory
{
    public function create(InvoiceSequence $invoiceSequence): InvoiceForm;
}
