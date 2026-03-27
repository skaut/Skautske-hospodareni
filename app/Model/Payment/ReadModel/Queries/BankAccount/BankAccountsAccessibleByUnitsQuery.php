<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries\BankAccount;

use App\Model\Payment\ReadModel\QueryHandlers\BankAccount\BankAccountsAccessibleByUnitsQueryHandler;

/** @see BankAccountsAccessibleByUnitsQueryHandler */
final class BankAccountsAccessibleByUnitsQuery
{
    /** @param int[] $unitIds */
    public function __construct(private array $unitIds)
    {
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }
}
