<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\PairingCandidate;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IPaymentRepository;

use function array_filter;
use function array_map;
use function array_values;

class BankPairingCandidateProvider
{
    public function __construct(
        private readonly IPaymentRepository $payments,
        private readonly InvoiceRepository $invoices,
    ) {
    }

    /**
     * @param  int[]                  $groupIds
     * @return list<PairingCandidate>
     */
    public function getScopedCandidatesForGroups(array $groupIds): array
    {
        return array_values(
            array_map(
                static fn (Payment $payment): PairingCandidate => PairingCandidate::forPayment($payment),
                array_filter(
                    $this->payments->findByMultipleGroups($groupIds),
                    static fn (Payment $payment): bool => $payment->canBePaired(),
                ),
            ),
        );
    }

    /**
     * @param  int[]                  $sequenceIds
     * @return list<PairingCandidate>
     */
    public function getScopedCandidatesForSequences(array $sequenceIds): array
    {
        return array_values(
            array_map(
                static fn (\App\Model\Invoice\Entity\Invoice $invoice): PairingCandidate => PairingCandidate::forInvoice($invoice),
                $this->invoices->findOpenTransferInvoicesBySequenceIds($sequenceIds),
            ),
        );
    }

    /** @return list<PairingCandidate> */
    public function getDomainCandidatesForBankAccount(int $bankAccountId): array
    {
        return [
            ...array_map(
                static fn (Payment $payment): PairingCandidate => PairingCandidate::forPayment($payment),
                $this->payments->findOpenByBankAccount($bankAccountId),
            ),
            ...array_map(
                static fn (\App\Model\Invoice\Entity\Invoice $invoice): PairingCandidate => PairingCandidate::forInvoice($invoice),
                $this->invoices->findOpenTransferInvoicesForBankAccount($bankAccountId),
            ),
        ];
    }
}
