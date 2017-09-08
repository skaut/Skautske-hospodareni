<?php

namespace Model\Skautis;

use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IBankAccountImporter;
use Model\Payment\InvalidBankAccountNumberException;
use Skautis\Skautis;


class BankAccountImporter implements IBankAccountImporter
{

    /** @var Skautis */
    private $skautis;


    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }


    /**
     * {@inheritDoc}
     */
    public function import(int $unitId): array
    {
        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $unitId,
            'IsValid' => TRUE,
        ]);

        $result = [];
        foreach ($accounts as $account) {
            try {
                $result[] = AccountNumber::fromString($account->DisplayName);
            } catch (InvalidBankAccountNumberException $e) {
                // Skip invalid bank accounts
            }
        }
        return $result;
    }

}
