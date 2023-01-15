<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

use Model\Payment\VariableSymbol;

final class PaymentWasCreated
{
    public function __construct(private int $groupId, private VariableSymbol|null $variableSymbol = null)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getVariableSymbol(): VariableSymbol|null
    {
        return $this->variableSymbol;
    }
}
