<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

use App\Model\Bank\Entity\BankTransactionImportBatch;

final readonly class BankAccountDetail
{
    /**
     * @param list<BankAccountTransactionRow>|null $transactionRows
     * @param list<BankTransactionImportBatch>     $importBatches
     */
    public function __construct(
        public ?array $transactionRows,
        public array $importBatches,
        public ?string $focusTargetLabel = null,
        public ?string $warningMessage = null,
        public ?string $errorMessage = null,
    ) {
    }
}
