<?php

declare(strict_types=1);

namespace Model\Payment\BankAccount;

use Entity\Embeddable\AccountNumber;

interface IBankAccountImporter
{
    /** @return AccountNumber[] */
    public function import(int $unitId): array;
}
