<?php

declare(strict_types=1);

namespace App\Model\Bank;

final class ManualBankTransactionPairingResult
{
    /** @param list<string> $warnings */
    public function __construct(private readonly array $warnings = [])
    {
    }

    /** @return list<string> */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
