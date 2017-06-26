<?php

namespace Model\Payment;

use Assert\Assert;
use DateTimeImmutable;
use Model\Bank\Fio\Transaction;
use Model\DTO\Payment\BankAccount as BankAccountDTO;
use Model\DTO\Payment\BankAccountFactory;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IAccountNumberValidator;
use Model\Payment\Fio\IFioClient;
use Model\Payment\Repositories\IBankAccountRepository;
use Nette\Caching\Cache;

class BankAccountService
{

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IAccountNumberValidator */
    private $numberValidator;

    /** @var IUnitResolver */
    private $unitResolver;

    /** @var IFioClient */
    private $fio;

    /** @var Cache */
    private $fioCache;

    public function __construct(
        IBankAccountRepository $bankAccounts,
        IAccountNumberValidator $numberValidator,
        IUnitResolver $unitResolver,
        IFioClient $fio,
        Cache $fioCache
    )
    {
        $this->bankAccounts = $bankAccounts;
        $this->numberValidator = $numberValidator;
        $this->unitResolver = $unitResolver;
        $this->fio = $fio;
        $this->fioCache = $fioCache;
    }


    /**
     * @throws InvalidBankAccountNumberException
     */
    public function addBankAccount(int $unitId, string $name, string $prefix, string $number, string $bankCode, ?string $token): void
    {
        $accountNumber = new AccountNumber($prefix, $number, $bankCode, $this->numberValidator);
        $account = new BankAccount($unitId, $name, $accountNumber, $token, new DateTimeImmutable(), $this->unitResolver);

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
        $this->cleanFioCache($id);
    }


    /**
     * @throws BankAccountNotFoundException
     */
    public function removeBankAccount(int $id): void
    {
        $account = $this->bankAccounts->find($id);
        $this->bankAccounts->remove($account);
        $this->cleanFioCache($id);
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
        $accounts = $this->bankAccounts->findByUnit($this->unitResolver->getOfficialUnitId($unitId));
        $accounts = array_filter($accounts, function (BankAccount $a ) use($unitId){
            return $a->getUnitId() === $unitId || $a->isAllowedForSubunits();
        });

        return array_map(function (BankAccount $a) { return BankAccountFactory::create($a); }, $accounts);
    }


    /**
     * @return Transaction[]
     * @throws TokenNotSetException
     */
    public function getTransactions(int $bankAccountId, int $daysBack): array
    {
        Assert::that($daysBack)->greaterThan(0);
        $account = $this->bankAccounts->find($bankAccountId);
        $now = new DateTimeImmutable();
        return $this->fio->getTransactions($now->modify("- $daysBack days"), $now, $account);
    }


    private function cleanFioCache(int $bankAccountId): void
    {
        $this->fioCache->clean([Cache::TAGS => 'fio/' . $bankAccountId]);
    }

}
