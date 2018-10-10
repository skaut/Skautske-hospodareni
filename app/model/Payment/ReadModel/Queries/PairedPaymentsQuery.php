<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use DateTimeImmutable;
use Model\Payment\BankAccount\BankAccountId;

/**
 * @see PairedPaymentsQueryHandler
 */
final class PairedPaymentsQuery
{
    /** @var BankAccountId */
    private $bankAccountId;

    /** @var DateTimeImmutable */
    private $since;

    /** @var DateTimeImmutable */
    private $until;

    public function __construct(BankAccountId $bankAccountId, DateTimeImmutable $since, DateTimeImmutable $until)
    {
        $this->bankAccountId = $bankAccountId;
        $this->since         = $since;
        $this->until         = $until;
    }

    public function getBankAccountId() : BankAccountId
    {
        return $this->bankAccountId;
    }

    public function getSince() : DateTimeImmutable
    {
        return $this->since;
    }

    public function getUntil() : DateTimeImmutable
    {
        return $this->until;
    }
}
