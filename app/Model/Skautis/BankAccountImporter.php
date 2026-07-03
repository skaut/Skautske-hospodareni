<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Payment\BankAccount\IBankAccountImporter;
use App\Model\Payment\InvalidBankAccountNumber;
use Skautis\Skautis;
use stdClass;

class BankAccountImporter implements IBankAccountImporter
{
    public function __construct(private Skautis $skautis)
    {
    }

    /** @return AccountNumber[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function import(int $unitId): array
    {
        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $unitId,
            'IsValid' => true,
        ]);

        if (! is_iterable($accounts)) {
            return [];
        }

        $result = [];
        foreach ($accounts as $account) {
            if (! $account instanceof stdClass) {
                continue;
            }

            try {
                $result[] = AccountNumber::fromString($account->DisplayName);
            } catch (InvalidBankAccountNumber) {
                // Skip invalid bank accounts
            }
        }

        return $result;
    }
}
