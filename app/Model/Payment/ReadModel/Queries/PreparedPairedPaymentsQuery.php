<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\BankAccount\BankAccountId;

/** @see PreparedPaymentsQueryHandler */
final class PreparedPairedPaymentsQuery
{
    public function __construct(private BankAccountId $bankAccountId)
    {
    }

    public function getBankAccountId(): BankAccountId
    {
        return $this->bankAccountId;
    }
}
