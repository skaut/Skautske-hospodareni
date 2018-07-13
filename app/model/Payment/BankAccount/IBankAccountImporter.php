<?php

declare(strict_types=1);

namespace Model\Payment\BankAccount;

interface IBankAccountImporter
{
    /**
     * @return AccountNumber[]
     */
    public function import(int $unitId) : array;
}
