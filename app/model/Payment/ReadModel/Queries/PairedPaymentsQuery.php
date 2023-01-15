<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use DateTimeImmutable;
use Model\Payment\BankAccount\BankAccountId;

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
