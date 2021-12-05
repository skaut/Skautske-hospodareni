<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\BankAccount\BankAccountId;

/**
 * @see PreparedPaymentsQueryHandler
 */
final class PreparedPairedPaymentsQuery
{
    private BankAccountId $bankAccountId;

    public function __construct(BankAccountId $bankAccountId)
    {
        $this->bankAccountId = $bankAccountId;
    }

    public function getBankAccountId(): BankAccountId
    {
        return $this->bankAccountId;
    }
}
