<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Payment;

use App\Model\Payment\VariableSymbol;

final class SplitPaymentPart
{
    public function __construct(
        private VariableSymbol $variableSymbol,
        private float $amount,
        private ?string $note = null,
    ) {
    }

    public function getVariableSymbol(): VariableSymbol
    {
        return $this->variableSymbol;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}
