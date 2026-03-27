<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Payment\BankAccount\IBankAccountImporter;

class BankAccountImporterStub implements IBankAccountImporter
{
    /** @return AccountNumber[] */
    public function import(int $unitId): array
    {
        return [];
    }
}
