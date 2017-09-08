<?php


namespace Model\Payment;


use Model\Payment\BankAccount\IBankAccountImporter;

class BankAccountImporterStub implements IBankAccountImporter
{

    public function import(int $unitId): array
    {
        return [];
    }

}
