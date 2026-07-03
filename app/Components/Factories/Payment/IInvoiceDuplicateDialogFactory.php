<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\InvoiceDuplicateDialog;

interface IInvoiceDuplicateDialogFactory
{
    /**
     * @param int[] $editableUnitIds
     */
    public function create(?int $invoiceSequenceId, array $editableUnitIds): InvoiceDuplicateDialog;
}
