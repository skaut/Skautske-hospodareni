<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries\BankAccount;

use Model\Payment\ReadModel\QueryHandlers\BankAccount\BankAccountsAccessibleByUnitsQueryHandler;

/**
 * @see BankAccountsAccessibleByUnitsQueryHandler
 */
final class BankAccountsAccessibleByUnitsQuery
{
    /** @var int[] */
    private array $unitIds;

    /**
     * @param int[] $unitIds
     */
    public function __construct(array $unitIds)
    {
        $this->unitIds = $unitIds;
    }

    /**
     * @return int[]
     */
    public function getUnitIds() : array
    {
        return $this->unitIds;
    }
}
