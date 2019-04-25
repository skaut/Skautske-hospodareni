<?php

declare(strict_types=1);

namespace Model\Payment;

use Assert\Assert;
use DateTimeImmutable;
use Model\Bank\Fio\Transaction;
use Model\BankTimeLimit;
use Model\BankTimeout;
use Model\DTO\Payment\BankAccount as BankAccountDTO;
use Model\DTO\Payment\BankAccountFactory;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IBankAccountImporter;
use Model\Payment\Fio\IFioClient;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Nette\Caching\Cache;
use function array_filter;
use function array_map;
use function count;
use function in_array;
use function sprintf;

class BankAccountService
{
    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IGroupRepository */
    private $groups;

    /** @var IUnitResolver */
    private $unitResolver;

    /** @var IFioClient */
    private $fio;

    /** @var IBankAccountImporter */
    private $importer;

    /** @var Cache */
    private $fioCache;

    public function __construct(
        IBankAccountRepository $bankAccounts,
        IGroupRepository $groups,
        IUnitResolver $unitResolver,
        IFioClient $fio,
        IBankAccountImporter $importer,
        Cache $fioCache
    ) {
        $this->bankAccounts = $bankAccounts;
        $this->groups       = $groups;
        $this->unitResolver = $unitResolver;
        $this->fio          = $fio;
        $this->importer     = $importer;
        $this->fioCache     = $fioCache;
    }

    public function addBankAccount(int $unitId, string $name, AccountNumber $number, ?string $token) : void
    {
        $account = new BankAccount($unitId, $name, $number, $token, new DateTimeImmutable(), $this->unitResolver);

        $this->bankAccounts->save($account);
    }

    /**
     * @throws BankAccountNotFound
     */
    public function updateBankAccount(int $id, string $name, AccountNumber $number, ?string $token) : void
    {
        $account = $this->bankAccounts->find($id);

        $account->update($name, $number, $token);

        $this->bankAccounts->save($account);
        $this->cleanFioCache($id);
    }

    /**
     * @throws BankAccountNotFound
     */
    public function removeBankAccount(int $id) : void
    {
        $account = $this->bankAccounts->find($id);

        $groups = $this->groups->findByBankAccount($id);

        foreach ($groups as $group) {
            $group->removeBankAccount();
            $this->groups->save($group);
        }

        $this->bankAccounts->remove($account);
        $this->cleanFioCache($id);
    }

    /**
     * @throws BankAccountNotFound
     */
    public function allowForSubunits(int $id) : void
    {
        $account = $this->bankAccounts->find($id);

        $account->allowForSubunits();

        $this->bankAccounts->save($account);
    }

    /**
     * @throws BankAccountNotFound
     */
    public function disallowForSubunits(int $id) : void
    {
        $account = $this->bankAccounts->find($id);

        $account->disallowForSubunits();

        foreach ($this->groups->findByBankAccount($id) as $group) {
            if ($group->getUnitIds() === [$account->getUnitId()]) {
                continue;
            }

            $group->removeBankAccount();
            $this->groups->save($group);
        }

        $this->bankAccounts->save($account);
    }

    public function find(int $id) : ?BankAccountDTO
    {
        try {
            return BankAccountFactory::create($this->bankAccounts->find($id));
        } catch (BankAccountNotFound $e) {
            return null;
        }
    }

    /**
     * @param int[] $ids
     *
     * @return BankAccountDTO[]
     *
     * @throws BankAccountNotFound
     */
    public function findByIds(array $ids) : array
    {
        Assert::thatAll($ids)->integer();
        $accounts = $this->bankAccounts->findByIds($ids);

        return array_map(
            function (BankAccount $a) {
                return BankAccountFactory::create($a);
            },
            $accounts
        );
    }

    /**
     * @return BankAccountDTO[]
     */
    public function findByUnit(int $unitId) : array
    {
        $accounts = $this->bankAccounts->findByUnit($this->unitResolver->getOfficialUnitId($unitId));
        $accounts = array_filter(
            $accounts,
            function (BankAccount $a) use ($unitId) {
                return $a->getUnitId() === $unitId || $a->isAllowedForSubunits();
            }
        );

        return array_map(
            function (BankAccount $a) {
                return BankAccountFactory::create($a);
            },
            $accounts
        );
    }

    /**
     * @return Transaction[]
     *
     * @throws TokenNotSet
     * @throws BankTimeout
     * @throws BankTimeLimit
     */
    public function getTransactions(int $bankAccountId, int $daysBack) : array
    {
        Assert::that($daysBack)->greaterThan(0);
        $account = $this->bankAccounts->find($bankAccountId);
        $now     = new DateTimeImmutable();

        return $this->fio->getTransactions($now->modify(sprintf('- %d days', $daysBack)), $now, $account);
    }

    /**
     * @throws BankAccountNotFound When no bank accounts were imported.
     */
    public function importFromSkautis(int $unitId) : void
    {
        $now     = new DateTimeImmutable();
        $numbers = $this->getImportableBankAccounts($unitId);

        if (count($numbers) === 0) {
            throw new BankAccountNotFound();
        }

        $i = 1;
        foreach ($numbers as $number) {
            $this->bankAccounts->save(
                new BankAccount($unitId, 'Importovaný účet (' . $i++ . ')', $number, null, $now, $this->unitResolver)
            );
        }
    }

    /**
     * @return AccountNumber[]
     */
    private function getImportableBankAccounts(int $unitId) : array
    {
        $unitId   = $this->unitResolver->getOfficialUnitId($unitId);
        $accounts = $this->bankAccounts->findByUnit($unitId);
        $numbers  = array_map(
            function (BankAccount $a) {
                return (string) $a->getNumber();
            },
            $accounts
        );

        $imported = $this->importer->import($unitId);

        return array_filter(
            $imported,
            function (AccountNumber $n) use ($numbers) {
                return ! in_array((string) $n, $numbers, true);
            }
        );
    }

    /**
     * Cleans cached transactions for account
     */
    private function cleanFioCache(int $bankAccountId) : void
    {
        $this->fioCache->clean([Cache::TAGS => 'fio/' . $bankAccountId]);
    }
}
