<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers\BankAccount;

use App\Model\DTO\Payment\BankAccount;
use App\Model\DTO\Payment\BankAccountFactory;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Services\IBankAccountAccessChecker;

use function array_map;
use function array_unique;

final class BankAccountsAccessibleByUnitsQueryHandler
{
    public function __construct(
        private IBankAccountAccessChecker $accessChecker,
        private IBankAccountRepository $bankAccounts,
        private IUnitResolver $unitResolver,
    ) {
    }

    /** @return BankAccount[] */
    public function __invoke(BankAccountsAccessibleByUnitsQuery $query): array
    {
        $unitIds = $query->getUnitIds();
        $officialUnitIds = array_unique(array_map([$this->unitResolver, 'getOfficialUnitId'], $unitIds));

        $bankAccounts = [];

        foreach ($officialUnitIds as $unitId) {
            foreach ($this->bankAccounts->findByUnit($unitId) as $bankAccount) {
                if (! $this->accessChecker->allUnitsHaveAccessToBankAccount($unitIds, $bankAccount->getId())) {
                    continue;
                }

                $bankAccounts[] = BankAccountFactory::create($bankAccount);
            }
        }

        return $bankAccounts;
    }
}
