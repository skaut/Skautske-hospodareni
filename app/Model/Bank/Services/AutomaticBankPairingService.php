<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Manager\BankTransactionPairingManager;
use App\Model\Bank\PairingCandidate;
use App\Model\Payment\Payment;
use DateTimeImmutable;

use function array_values;
use function count;
use function number_format;

class AutomaticBankPairingService
{
    public function __construct(private readonly BankTransactionPairingManager $pairings)
    {
    }

    /**
     * @param list<BankTransaction>  $transactions
     * @param list<PairingCandidate> $domainCandidates
     * @param list<PairingCandidate> $scopeCandidates
     *
     * @return array{payments: list<Payment>, invoices: list<\App\Model\Invoice\Entity\Invoice>}
     */
    public function pair(
        array $transactions,
        array $domainCandidates,
        array $scopeCandidates,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy = null,
    ): array {
        $transactionsByKey = [];
        foreach ($transactions as $transaction) {
            $matchKey = $this->createTransactionMatchKey($transaction);

            if ($matchKey === null) {
                continue;
            }

            $transactionsByKey[$matchKey][] = $transaction;
        }

        $domainCandidatesByKey = $this->groupCandidatesByKey($domainCandidates);
        $scopeCandidatesByKey = $this->groupCandidatesByKey($scopeCandidates);

        $pairedPayments = [];
        $pairedInvoices = [];
        $usedCandidates = [];

        foreach ($transactionsByKey as $matchKey => $matchedTransactions) {
            if (count($matchedTransactions) !== 1) {
                continue;
            }

            if (count($domainCandidatesByKey[$matchKey] ?? []) !== 1) {
                continue;
            }

            if (count($scopeCandidatesByKey[$matchKey] ?? []) !== 1) {
                continue;
            }

            $candidate = $scopeCandidatesByKey[$matchKey][0];
            $candidateIdentity = $candidate->getIdentityKey();

            if (isset($usedCandidates[$candidateIdentity])) {
                continue;
            }

            $transaction = $matchedTransactions[0];
            $payment = $candidate->getPayment();
            if ($payment !== null) {
                if (! $this->pairings->pairPaymentWithoutFlush(
                    $transaction,
                    $payment,
                    $pairedAt,
                    BankTransactionPairingMode::AUTOMATIC,
                    $pairedBy,
                )) {
                    continue;
                }

                $pairedPayments[] = $payment;
                $usedCandidates[$candidateIdentity] = true;
                continue;
            }

            $invoice = $candidate->getInvoice();
            if ($invoice === null) {
                continue;
            }

            if (! $this->pairings->pairInvoiceWithoutFlush(
                $transaction,
                $invoice,
                $pairedAt,
                BankTransactionPairingMode::AUTOMATIC,
                $pairedBy,
            )) {
                continue;
            }

            $pairedInvoices[] = $invoice;
            $usedCandidates[$candidateIdentity] = true;
        }

        return [
            'payments' => array_values($pairedPayments),
            'invoices' => array_values($pairedInvoices),
        ];
    }

    /**
     * @param list<PairingCandidate> $candidates
     *
     * @return array<string, list<PairingCandidate>>
     */
    private function groupCandidatesByKey(array $candidates): array
    {
        $grouped = [];

        foreach ($candidates as $candidate) {
            $grouped[$candidate->getMatchKey()][] = $candidate;
        }

        return $grouped;
    }

    private function createTransactionMatchKey(BankTransaction $transaction): ?string
    {
        $variableSymbol = $transaction->getVariableSymbol();

        if ($variableSymbol === null) {
            return null;
        }

        return $variableSymbol.'|'.number_format($transaction->getAmount(), 2, '.', '');
    }
}
