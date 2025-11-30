<?php

declare(strict_types=1);

namespace Manager;

use Entity\BankAccount;

class BankAccountManager extends AbstractManager
{
    public function getEntityClass(): string
    {
        return BankAccount::class;
    }
}
