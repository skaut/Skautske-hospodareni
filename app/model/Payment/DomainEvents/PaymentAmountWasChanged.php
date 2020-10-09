<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

use Model\Payment\VariableSymbol;

final class PaymentAmountWasChanged
{
    private int $groupId;

    private ?VariableSymbol $variableSymbol;

    public function __construct(int $groupId, ?VariableSymbol $variableSymbol)
    {
        $this->groupId        = $groupId;
        $this->variableSymbol =  $variableSymbol;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }

    public function getVariableSymbol() : ?VariableSymbol
    {
        return $this->variableSymbol;
    }
}
