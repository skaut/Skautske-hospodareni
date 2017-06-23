<?php

namespace Model\Payment;

use DateTimeImmutable;
use Model\DTO\Payment\BankAccount as BankAccountDTO;
use Model\DTO\Payment\BankAccountFactory;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IAccountNumberValidator;
use Model\Payment\Repositories\IBankAccountRepository;

class BankAccountService
{

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IAccountNumberValidator */
    private $numberValidator;

    public function __construct(IBankAccountRepository $bankAccounts, IAccountNumberValidator $numberValidator)
    {
        $this->bankAccounts = $bankAccounts;
        $this->numberValidator = $numberValidator;
    }


    /**
     * @throws InvalidBankAccountNumberException
     */
    public function addBankAccount(int $unitId, string $name, string $prefix, string $number, string $bankCode, ?string $token): void
    {
        $accountNumber = new AccountNumber($prefix, $number, $bankCode, $this->numberValidator);
        $account = new BankAccount($unitId, $name, $accountNumber, $token, new DateTimeImmutable());

        $this->bankAccounts->save($account);
    }


    /**
     * @throws BankAccountNotFoundException
     * @throws InvalidBankAccountNumberException
     */
    public function updateBankAccount(int $id, string $name, string $prefix, string $number, string $bankCode, ?string $token): void
    {
        $account = $this->bankAccounts->find($id);

        $accountNumber = new AccountNumber($prefix, $number, $bankCode, $this->numberValidator);
        $account->update($name, $accountNumber, $token);

        $this->bankAccounts->save($account);
    }


    /**
     * @throws BankAccountNotFoundException
     */
    public function removeBankAccount(int $id): void
    {
        $account = $this->bankAccounts->find($id);
        $this->bankAccounts->remove($account);
    }


    /**
     * @param int $id
     * @throws BankAccountNotFoundException
     */
    public function allowForSubunits(int $id): void
    {
        $account = $this->bankAccounts->find($id);

        $account->allowForSubunits();

        $this->bankAccounts->save($account);
    }


    public function find(int $id): ?BankAccountDTO
    {
        try {
            return BankAccountFactory::create($this->bankAccounts->find($id));
        } catch(BankAccountNotFoundException $e) {
            return NULL;
        }
    }


    /**
     * @return BankAccountDTO[]
     */
    public function findByUnit(int $unitId): array
    {
        $accounts = $this->bankAccounts->findByUnit($unitId);
        return array_map(function (BankAccount $a) { return BankAccountFactory::create($a); }, $accounts);
    }

}
