<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;

final readonly class BankAccountTransactionRow
{
    /**
     * @param list<BankAccountManualCandidate> $manualCandidates
     * @param list<BankAccountTransactionLink> $exactCandidates
     * @param list<BankAccountTransactionLink> $variableSymbolCandidates
     */
    public function __construct(
        public BankTransaction $transaction,
        public ?BankTransactionPairing $pairing,
        public ?BankAccountTransactionLink $pairingLabel,
        public array $manualCandidates,
        public array $exactCandidates,
        public array $variableSymbolCandidates,
        public ?string $conflictReason,
        public bool $isFocusMatch,
    ) {
    }
}
