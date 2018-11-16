<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IBankAccountImporter;

class BankAccountImporterStub implements IBankAccountImporter
{
    /**
     * @return AccountNumber[]
     */
    public function import(int $unitId) : array
    {
        return [];
    }
}
