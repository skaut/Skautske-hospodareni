<?php

declare(strict_types=1);

namespace App\Model\Bank\Manager;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Infrastructure\Manager\AbstractManager;

class BankAccountManager extends AbstractManager
{
    public function getEntityClass(): string
    {
        return BankAccount::class;
    }
}
