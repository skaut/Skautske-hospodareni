<?php

declare(strict_types=1);

namespace Stubs;

use App\Model\Payment\Services\IBankAccountAccessChecker;

final class BankAccountAccessCheckerStub implements IBankAccountAccessChecker
{
    /** @param int[] $unitIds */
    public function allUnitsHaveAccessToBankAccount(array $unitIds, int $bankAccountId): bool
    {
        return true;
    }
}
