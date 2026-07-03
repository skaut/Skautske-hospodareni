<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\BankAccount;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Payment\Commands\BankAccount\CreateBankAccount;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\Repositories\IBankAccountRepository;
use DateTimeImmutable;

final class CreateBankAccountHandler
{
    public function __construct(private IBankAccountRepository $bankAccounts, private IUnitResolver $unitResolver)
    {
    }

    public function __invoke(CreateBankAccount $command): void
    {
        $this->bankAccounts->save(
            new BankAccount(
                $command->getUnitId(),
                $command->getName(),
                $command->getNumber(),
                $command->getToken(),
                new DateTimeImmutable(),
                $this->unitResolver,
                $command->getTransactionSource(),
            ),
        );
    }
}
