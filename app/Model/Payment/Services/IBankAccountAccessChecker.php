<?php

declare(strict_types=1);

namespace App\Model\Payment\Services;

use App\Model\Payment\BankAccountNotFound;

interface IBankAccountAccessChecker
{
    /**
     * @param int[] $unitIds
     *
     * @throws BankAccountNotFound
     */
    public function allUnitsHaveAccessToBankAccount(array $unitIds, int $bankAccountId): bool;
}
