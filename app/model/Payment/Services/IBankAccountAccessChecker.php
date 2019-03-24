<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Payment\BankAccountNotFound;

interface IBankAccountAccessChecker
{
    /**
     * @param int[] $unitIds
     * @throws BankAccountNotFound
     */
    public function allUnitsHaveAccessToBankAccount(array $unitIds, int $bankAccountId) : bool;
}
