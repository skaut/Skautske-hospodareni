<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Bank\InvoiceBankService;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;

use function array_filter;
use function array_map;
use function count;

final class InvoiceSequencePairButtonScope implements PairButtonScope
{
    /** @param int[] $sequenceIds */
    public function __construct(
        private readonly InvoiceBankService $invoiceBankService,
        private readonly InvoiceSequenceRepository $invoiceSequences,
        private readonly PairButtonBankAccountSupport $bankAccountSupport,
        private readonly array $sequenceIds,
    ) {
    }

    public function getItemsCount(): int
    {
        return count($this->sequenceIds);
    }

    public function canPair(): bool
    {
        if ($this->sequenceIds === []) {
            return false;
        }

        $bankAccountIds = array_map(
            static fn (InvoiceSequence $sequence): ?int => $sequence->getBankAccount()?->getId(),
            $this->invoiceSequences->findBy(['id' => $this->sequenceIds]),
        );

        return $this->bankAccountSupport->hasPairableBankAccount(array_filter($bankAccountIds));
    }

    public function getDaysBackDefault(): int
    {
        return InvoiceBankService::DAYS_BACK_DEFAULT;
    }

    public function getDisabledReason(): string
    {
        return 'Fakturační řada nemá dostupný bankovní účet pro párování nebo u FIO účtu chybí API token.';
    }

    public function pair(?int $daysBack = null): array
    {
        $pairedCount = $this->invoiceBankService->pairAllSequences($this->sequenceIds, $daysBack);

        return [
            new PairButtonFlashMessage(
                $pairedCount > 0
                    ? 'Bankovní úhrady byly spárovány s '.$pairedCount.' fakturami.'
                    : 'Žádné faktury nebyly bankovně spárovány.',
                $pairedCount > 0 ? 'success' : 'info',
            ),
        ];
    }
}
