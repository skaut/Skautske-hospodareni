<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;


final class PaymentWasCreated
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
