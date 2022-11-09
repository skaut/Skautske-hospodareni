<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IBankAccountImporter;
use Model\Payment\InvalidBankAccountNumber;
use Skautis\Skautis;
use stdClass;

use function assert;

class BankAccountImporter implements IBankAccountImporter
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /** @return AccountNumber[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function import(int $unitId): array
    {
        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $unitId,
            'IsValid' => true,
        ]);

        $result = [];
        foreach ($accounts as $account) {
            assert($account instanceof stdClass);
            try {
                $result[] = AccountNumber::fromString($account->DisplayName);
            } catch (InvalidBankAccountNumber $e) {
                // Skip invalid bank accounts
            }
        }

        return $result;
    }
}
