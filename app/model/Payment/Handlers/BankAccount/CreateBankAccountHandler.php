<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\BankAccount;

use DateTimeImmutable;
use Model\Payment\BankAccount;
use Model\Payment\Commands\BankAccount\CreateBankAccount;
use Model\Payment\IUnitResolver;
use Model\Payment\Repositories\IBankAccountRepository;

final class CreateBankAccountHandler
{
    private IBankAccountRepository $bankAccounts;

    private IUnitResolver $unitResolver;

    public function __construct(IBankAccountRepository $bankAccounts, IUnitResolver $unitResolver)
    {
        $this->bankAccounts = $bankAccounts;
        $this->unitResolver = $unitResolver;
    }

    public function __invoke(CreateBankAccount $command) : void
    {
        $this->bankAccounts->save(
            new BankAccount(
                $command->getUnitId(),
                $command->getName(),
                $command->getNumber(),
                $command->getToken(),
                new DateTimeImmutable(),
                $this->unitResolver
            )
        );
    }
}
