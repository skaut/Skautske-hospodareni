<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Payment\Payment;
use Cake\Chronos\ChronosDate;

use function count;

final readonly class BankAccountPairingRunResult
{
    /**
     * @param list<mixed>   $scopeItems
     * @param list<Payment> $payments
     * @param list<Invoice> $invoices
     */
    public function __construct(
        public BankAccount $bankAccount,
        public ChronosDate $pairSince,
        public ChronosDate $pairedUntil,
        public array $scopeItems,
        public array $payments,
        public array $invoices,
    ) {
    }

    public function getPairedPaymentsCount(): int
    {
        return count($this->payments);
    }

    public function getPairedInvoicesCount(): int
    {
        return count($this->invoices);
    }
}
