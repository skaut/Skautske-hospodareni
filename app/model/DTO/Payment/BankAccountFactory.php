<?php

namespace Model\DTO\Payment;

use Model\Payment\BankAccount as BankAccountEntity;
use Nette\StaticClass;

final class BankAccountFactory
{

    use StaticClass;

    public static function create(BankAccountEntity $bankAccount): BankAccount
    {
        return new BankAccount(
            $bankAccount->getId(),
            $bankAccount->getUnitId(),
            $bankAccount->getName(),
            $bankAccount->getNumber(),
            $bankAccount->getToken(),
            $bankAccount->getCreatedAt(),
            $bankAccount->isAllowedForSubunits()
        );
    }

}
