<?php

declare(strict_types=1);

namespace App\Model\Payment\DomainEvents;

use App\Model\Payment\VariableSymbol;

final class PaymentVariableSymbolWasChanged
{
    public function __construct(private int $groupId, private ?VariableSymbol $variableSymbol = null)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getVariableSymbol(): ?VariableSymbol
    {
        return $this->variableSymbol;
    }
}
