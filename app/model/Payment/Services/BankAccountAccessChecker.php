<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Payment\IUnitResolver;
use Model\Payment\Repositories\IBankAccountRepository;

final class BankAccountAccessChecker implements IBankAccountAccessChecker
{
    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(IBankAccountRepository $bankAccounts, IUnitResolver $unitResolver)
    {
        $this->bankAccounts = $bankAccounts;
        $this->unitResolver = $unitResolver;
    }

    /**
     * @param int[] $unitIds
     */
    public function allUnitsHaveAccessToBankAccount(array $unitIds, int $bankAccountId) : bool
    {
        $bankAccount = $this->bankAccounts->find($bankAccountId);

        if ($unitIds === [$bankAccount->getUnitId()]) {
            return true;
        }

        if (! $bankAccount->isAllowedForSubunits()) {
            return false;
        }

        foreach ($unitIds as $unitId) {
            if ($this->unitResolver->getOfficialUnitId($unitId) !== $bankAccount->getUnitId()) {
                return false;
            }
        }

        return true;
    }
}
