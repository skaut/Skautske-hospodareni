<?php


namespace Model\Payment\DomainEvents;


final class PaymentVariableSymbolWasChanged
{

    /** @var int */
    private $groupId;

    /** @var int|NULL */
    private $variableSymbol;


    public function __construct(int $groupId, ?int $variableSymbol)
    {
        $this->groupId = $groupId;
        $this->variableSymbol = $variableSymbol;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getVariableSymbol(): ?int
    {
        return $this->variableSymbol;
    }

}
