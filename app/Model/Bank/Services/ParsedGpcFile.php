<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Transaction;

final class ParsedGpcFile
{
    /** @param list<Transaction> $transactions */
    public function __construct(
        public readonly ?string $statementAccountNumber,
        public readonly array $transactions,
    ) {
    }
}
