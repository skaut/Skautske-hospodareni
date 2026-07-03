<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Bank\Entity\BankAccount as BankAccountEntity;
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
            $bankAccount->getTransactionSource(),
            $bankAccount->getCreatedAt(),
            $bankAccount->isAllowedForSubunits(),
        );
    }
}
