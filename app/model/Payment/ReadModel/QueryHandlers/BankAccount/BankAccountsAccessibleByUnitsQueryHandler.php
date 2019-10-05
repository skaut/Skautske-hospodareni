<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers\BankAccount;

use Model\DTO\Payment\BankAccount;
use Model\DTO\Payment\BankAccountFactory;
use Model\Payment\IUnitResolver;
use Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Services\IBankAccountAccessChecker;
use function array_map;
use function array_unique;

final class BankAccountsAccessibleByUnitsQueryHandler
{
    /** @var IBankAccountAccessChecker */
    private $accessChecker;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(
        IBankAccountAccessChecker $accessChecker,
        IBankAccountRepository $bankAccounts,
        IUnitResolver $unitResolver
    ) {
        $this->accessChecker = $accessChecker;
        $this->bankAccounts  = $bankAccounts;
        $this->unitResolver  = $unitResolver;
    }

    /**
     * @return BankAccount[]
     */
    public function __invoke(BankAccountsAccessibleByUnitsQuery $query) : array
    {
        $unitIds         = $query->getUnitIds();
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
