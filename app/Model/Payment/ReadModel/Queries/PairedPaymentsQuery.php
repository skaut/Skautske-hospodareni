<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\BankAccount\BankAccountId;
use DateTimeImmutable;

/** @see PairedPaymentsQueryHandler */
final class PairedPaymentsQuery
{
    public function __construct(private BankAccountId $bankAccountId, private DateTimeImmutable $since, private DateTimeImmutable $until)
    {
    }

    public function getBankAccountId(): BankAccountId
    {
        return $this->bankAccountId;
    }

    public function getSince(): DateTimeImmutable
    {
        return $this->since;
    }

    public function getUntil(): DateTimeImmutable
    {
        return $this->until;
    }
}
