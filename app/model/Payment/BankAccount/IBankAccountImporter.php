<?php

namespace Model\Payment\BankAccount;


interface IBankAccountImporter
{

    /**
     * @return AccountNumber[]
     */
    public function import(int $unitId): array;

}
