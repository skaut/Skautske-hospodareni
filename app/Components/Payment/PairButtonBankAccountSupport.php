<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\DTO\Payment\BankAccount as BankAccountDto;
use App\Model\Payment\BankAccountService;

final class PairButtonBankAccountSupport
{
    public function __construct(private readonly BankAccountService $bankAccounts)
    {
    }

    /** @param int[] $bankAccountIds */
    public function hasPairableBankAccount(array $bankAccountIds): bool
    {
        if ($bankAccountIds === []) {
            return false;
        }

        foreach ($this->bankAccounts->findByIds($bankAccountIds) as $account) {
            if ($this->supportsPairing($account)) {
                return true;
            }
        }

        return false;
    }

    private function supportsPairing(BankAccountDto $account): bool
    {
        return $account->getTransactionSource()->value === BankTransactionSource::GPC->value
            || $account->getToken() !== null;
    }
}
