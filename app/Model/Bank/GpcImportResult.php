<?php

declare(strict_types=1);

namespace App\Model\Bank;

use App\Model\Bank\Entity\BankTransactionImportBatch;
use Cake\Chronos\ChronosDate;

final class GpcImportResult
{
    public function __construct(
        public readonly BankTransactionImportBatch $batch,
        /**
         * Date of the earliest transaction in the file, also of those imported before.
         * Null for a statement without transactions.
         */
        public readonly ?ChronosDate $statementStart,
    ) {
    }
}
