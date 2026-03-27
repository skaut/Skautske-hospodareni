<?php

declare(strict_types=1);

namespace App\Model\Bank;

use App\Model\Bank\Services\BankAccountPairingRunner;
use App\Model\Bank\Services\BankPairingCandidateProvider;
use App\Model\Bank\Services\BankTransactionService;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use Assert\Assert;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;
use function min;

final class InvoiceBankService
{
    public const DAYS_BACK_DEFAULT = 60;

    public function __construct(
        private readonly InvoiceSequenceRepository $invoiceSequences,
        private readonly BankTransactionService $transactions,
        private readonly BankPairingCandidateProvider $pairingCandidates,
        private readonly BankAccountPairingRunner $pairingRunner,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param int[] $sequenceIds
     */
    public function pairAllSequences(array $sequenceIds, ?int $daysBack = null): int
    {
        return $this->pairSequences($sequenceIds, $daysBack, true);
    }

    public function pairAutomaticSequences(): int
    {
        $sequenceIds = array_map(
            static fn (InvoiceSequence $sequence): int => $sequence->getId(),
            $this->invoiceSequences->findAutomaticPairingEnabled(),
        );

        return $this->pairSequences($sequenceIds, null, false);
    }

    /**
     * @param int[] $sequenceIds
     */
    private function pairSequences(array $sequenceIds, ?int $daysBack, bool $refreshTransactions): int
    {
        Assert::thatAll($sequenceIds)->integer();
        Assert::that($daysBack)->nullOr()->min(1);

        if ($sequenceIds === []) {
            return 0;
        }

        $pairedCount = 0;

        /** @var InvoiceSequence[] $sequences */
        $sequences = $this->invoiceSequences->findBy(['id' => $sequenceIds]);

        foreach ($this->pairingRunner->run(
            $sequences,
            static fn (InvoiceSequence $sequence): ?int => $sequence->getBankAccount()?->getId(),
            static fn (int $bankAccountId, array $accountSequences): ?\App\Model\Bank\Entity\BankAccount => $accountSequences[0]->getBankAccount(),
            fn (array $accountSequences): array => $this->pairingCandidates->getScopedCandidatesForSequences(
                array_map(static fn (InvoiceSequence $sequence): int => $sequence->getId(), $accountSequences),
            ),
            fn (array $accountSequences): ChronosDate => $daysBack !== null
                ? ChronosDate::today()->subDays($daysBack)
                : $this->resolvePairingIntervalStart($accountSequences),
            fn (Entity\BankAccount $bankAccount, ChronosDate $pairSince, ChronosDate $now): array => $refreshTransactions
                ? $this->transactions->getPersistentTransactionsForPeriod($bankAccount, $pairSince, $now)
                : $this->transactions->getStoredTransactionsForPeriod($bankAccount, $pairSince, $now),
            skipWhenNoScopeCandidates: true,
        ) as $result) {
            foreach ($result->invoices as $invoice) {
                $this->entityManager->persist($invoice);
            }

            $pairedCount += $result->getPairedInvoicesCount();

            if ($daysBack !== null) {
                if ($result->invoices !== []) {
                    $this->entityManager->flush();
                }

                continue;
            }

            $this->updateLastPairing($result->scopeItems, $result->pairedUntil->toNative());
            $this->entityManager->flush();
        }

        return $pairedCount;
    }

    /** @param InvoiceSequence[] $sequences */
    private function resolvePairingIntervalStart(array $sequences): ChronosDate
    {
        $defaultStart = ChronosDate::today()->subDays(self::DAYS_BACK_DEFAULT);

        if ($sequences === []) {
            return $defaultStart;
        }

        return new ChronosDate(
            min(
                array_map(
                    static fn (InvoiceSequence $sequence): DateTimeImmutable => $sequence->getLastPairing()
                        ?? ChronosDate::today()->subDays($sequence->getPairingDaysBack() ?? self::DAYS_BACK_DEFAULT)->toNative(),
                    $sequences,
                ),
            ),
        );
    }

    /** @param InvoiceSequence[] $sequences */
    private function updateLastPairing(array $sequences, DateTimeImmutable $time): void
    {
        foreach ($sequences as $sequence) {
            $sequence->updateLastPairing($time);
            $this->entityManager->persist($sequence);
        }
    }
}
