<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

final readonly class BankAccountFocusTarget
{
    public function __construct(public string $targetKey, public string $label)
    {
    }
}
