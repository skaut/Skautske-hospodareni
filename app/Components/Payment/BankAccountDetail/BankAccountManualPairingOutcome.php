<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

final readonly class BankAccountManualPairingOutcome
{
    /** @param list<string> $warnings */
    public function __construct(public string $successMessage, public array $warnings)
    {
    }
}
