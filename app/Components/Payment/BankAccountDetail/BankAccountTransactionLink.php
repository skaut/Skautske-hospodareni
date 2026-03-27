<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

final readonly class BankAccountTransactionLink
{
    public function __construct(
        public string $type,
        public string $label,
        public ?string $url,
        public string $targetKey,
    ) {
    }
}
