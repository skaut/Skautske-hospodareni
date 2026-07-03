<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\BankTransactionAmountMismatch;
use App\Model\Bank\BankTransactionPairingNotAllowed;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Manager\BankTransactionPairingManager;
use App\Model\Bank\ManualBankTransactionPairingResult;
use App\Model\Bank\PairingCandidate;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IGroupRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use function number_format;

class BankTransactionPairingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AutomaticBankPairingService $automaticPairing,
        private readonly BankTransactionPairingManager $pairings,
        private readonly BankTransactionPairingRepository $pairingRepository,
        private readonly IGroupRepository $groups,
    ) {
    }

    /**
     * @param list<BankTransaction>  $transactions
     * @param list<PairingCandidate> $domainCandidates
     * @param list<PairingCandidate> $scopeCandidates
     *
     * @return array{payments: list<Payment>, invoices: list<Invoice>}
     */
    public function pairAutomatically(
        array $transactions,
        array $domainCandidates,
        array $scopeCandidates,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy = null,
    ): array {
        return $this->automaticPairing->pair($transactions, $domainCandidates, $scopeCandidates, $pairedAt, $pairedBy);
    }

    public function pairPaymentManually(
        BankTransaction $transaction,
        Payment $payment,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy = null,
    ): ManualBankTransactionPairingResult {
        $this->assertAmountsMatch($payment->getAmount(), $transaction->getAmount());
        $warnings = $this->collectPaymentWarnings($transaction, $payment);

        $this->entityManager->wrapInTransaction(function () use ($transaction, $payment, $pairedAt, $pairedBy): void {
            if (! $this->pairings->pairPaymentWithoutFlush($transaction, $payment, $pairedAt, BankTransactionPairingMode::MANUAL, $pairedBy)) {
                throw $this->createConflictException($transaction, $payment, null);
            }

            $this->entityManager->flush();
        });

        return new ManualBankTransactionPairingResult($warnings);
    }

    public function pairInvoiceManually(
        BankTransaction $transaction,
        Invoice $invoice,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy = null,
    ): ManualBankTransactionPairingResult {
        $this->assertAmountsMatch((float) (string) $invoice->getTotalAmount(), $transaction->getAmount());
        $warnings = $this->collectInvoiceWarnings($transaction, $invoice);

        $this->entityManager->wrapInTransaction(function () use ($transaction, $invoice, $pairedAt, $pairedBy): void {
            if (! $this->pairings->pairInvoiceWithoutFlush($transaction, $invoice, $pairedAt, BankTransactionPairingMode::MANUAL, $pairedBy)) {
                throw $this->createConflictException($transaction, null, $invoice);
            }

            $this->entityManager->flush();
        });

        return new ManualBankTransactionPairingResult($warnings);
    }

    public function cancelPaymentPairing(
        Payment $payment,
        DateTimeImmutable $cancelledAt,
        ?string $cancelledBy = null,
        ?string $reason = null,
    ): bool {
        return $this->entityManager->wrapInTransaction(function () use ($payment, $cancelledAt, $cancelledBy, $reason): bool {
            $cancelled = $this->pairings->cancelPaymentPairingWithoutFlush($payment, $cancelledAt, $cancelledBy, $reason);

            if ($cancelled) {
                $this->entityManager->flush();
            }

            return $cancelled;
        });
    }

    public function cancelInvoicePairing(
        Invoice $invoice,
        DateTimeImmutable $cancelledAt,
        ?string $cancelledBy = null,
        ?string $reason = null,
    ): bool {
        return $this->entityManager->wrapInTransaction(function () use ($invoice, $cancelledAt, $cancelledBy, $reason): bool {
            $cancelled = $this->pairings->cancelInvoicePairingWithoutFlush($invoice, $cancelledAt, $cancelledBy, $reason);

            if ($cancelled) {
                $this->entityManager->flush();
            }

            return $cancelled;
        });
    }

    /** @return list<string> */
    private function collectPaymentWarnings(BankTransaction $transaction, Payment $payment): array
    {
        $group = $this->groups->find($payment->getGroupId());
        $warnings = [];

        if ($payment->getVariableSymbol()?->toInt() !== $transaction->getVariableSymbol()) {
            $warnings[] = 'VS bankovní transakce se liší od VS platby.';
        }

        if ($group->getBankAccountId() !== null && $transaction->getBankAccount()->getId() !== $group->getBankAccountId()) {
            $warnings[] = 'Bankovní transakce pochází z jiného účtu než platba.';
        }

        return $warnings;
    }

    /** @return list<string> */
    private function collectInvoiceWarnings(BankTransaction $transaction, Invoice $invoice): array
    {
        $warnings = [];

        if ($invoice->getVariableSymbol()->toInt() !== $transaction->getVariableSymbol()) {
            $warnings[] = 'VS bankovní transakce se liší od VS faktury.';
        }

        if ($invoice->getBankAccount() !== null && $transaction->getBankAccount()->getId() !== $invoice->getBankAccount()->getId()) {
            $warnings[] = 'Bankovní transakce pochází z jiného účtu než faktura.';
        }

        return $warnings;
    }

    private function assertAmountsMatch(float $expectedAmount, float $transactionAmount): void
    {
        if ($this->normalizeAmount($expectedAmount) === $this->normalizeAmount($transactionAmount)) {
            return;
        }

        throw new BankTransactionAmountMismatch('Ruční bankovní párování vyžaduje přesnou shodu částky.');
    }

    private function createConflictException(BankTransaction $transaction, ?Payment $payment, ?Invoice $invoice): BankTransactionPairingNotAllowed
    {
        if ($this->pairingRepository->findActiveByTransaction($transaction) !== null) {
            return new BankTransactionPairingNotAllowed('Bankovní transakce už je spárovaná.');
        }

        if ($payment !== null && $this->pairingRepository->findActiveByPayment($payment) !== null) {
            return new BankTransactionPairingNotAllowed('Platba už má aktivní bankovní párování.');
        }

        if ($invoice !== null && $this->pairingRepository->findActiveByInvoice($invoice) !== null) {
            return new BankTransactionPairingNotAllowed('Faktura už má aktivní bankovní párování.');
        }

        return new BankTransactionPairingNotAllowed('Ruční bankovní párování nelze provést.');
    }

    private function normalizeAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
