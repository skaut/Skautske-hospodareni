<?php

declare(strict_types=1);

namespace App\Model\Payment\BankAccount;

use App\Model\Common\Embeddable\AccountNumber;

interface IBankAccountImporter
{
    /** @return AccountNumber[] */
    public function import(int $unitId): array;
}
