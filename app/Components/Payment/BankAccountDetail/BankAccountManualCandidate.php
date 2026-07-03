<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

final readonly class BankAccountManualCandidate
{
    /** @param list<string> $warnings */
    public function __construct(
        public string $type,
        public string $label,
        public string $url,
        public string $actionUrl,
        public array $warnings,
        public string $targetKey,
    ) {
    }
}
