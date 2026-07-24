<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionImportBatch;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Exception\BankWrongTokenAccount;
use App\Model\Bank\Repository\BankTransactionImportBatchRepository;
use App\Model\Bank\Services\BankTransactionService;
use App\Model\Bank\Transaction;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\UnitId;
use App\Model\DTO\Payment\BankAccount as BankAccountDTO;
use App\Model\DTO\Payment\BankAccountFactory;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\BankAccount\IBankAccountImporter;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Assert\Assert;
use DateTimeImmutable;
use InvalidArgumentException;
use Nette\Caching\Cache;
use Nette\Http\FileUpload;
use Utility\Cnb\BankAccountValidator;
use Utility\Cnb\BankInfoDTO;
use Utility\Cnb\BankNotFoundException;

use function array_filter;
use function array_map;
use function count;
use function in_array;
use function sprintf;

class BankAccountService
{
    protected BankAccountValidator $bankAccountValidator;

    public function __construct(
        private IBankAccountRepository $bankAccounts,
        private IGroupRepository $groups,
        private IUnitResolver $unitResolver,
        private BankTransactionService $transactions,
        private IBankAccountImporter $importer,
        private Cache $fioCache,
        private IPaymentRepository $payments,
        private InvoiceRepository $invoiceRepository,
        private InvoiceSequenceRepository $invoiceSequences,
        private BankTransactionImportBatchRepository $transactionImportBatches,
    ) {
        $this->bankAccountValidator = new BankAccountValidator();
    }

    /** @throws BankAccountNotFound */
    public function updateBankAccount(int $id, string $name, AccountNumber $number, ?string $token, ?BankTransactionSource $transactionSource = null): void
    {
        $account = $this->bankAccounts->find($id);
        $nextSource = $transactionSource ?? $account->getTransactionSource();

        if (
            $account->getTransactionSource()->value !== $nextSource->value
            && ($this->transactions->hasTransactions($account)
                || $this->payments->existsPairedPaymentForBankAccount($id)
                || $this->invoiceRepository->existsPairedInvoiceForBankAccount($id))
        ) {
            throw new BankTransactionSourceChangeNotAllowed('Zdroj transakcí lze změnit jen u účtu bez transakcí a bez párování.');
        }

        $account->update($name, $number, $token, $transactionSource);

        $this->bankAccounts->save($account);
        $this->cleanFioCache($id);
    }

    /** @throws BankAccountNotFound */
    public function removeBankAccount(int $id): void
    {
        $account = $this->bankAccounts->find($id);

        $this->detachBankAccountFromGroups($account);
        $this->detachBankAccountFromInvoiceSequences($account);

        $this->bankAccounts->remove($account);
        $this->cleanFioCache($id);
    }

    /** @throws BankAccountNotFound */
    public function allowForSubunits(int $id): void
    {
        $account = $this->bankAccounts->find($id);

        $account->allowForSubunits();

        $this->bankAccounts->save($account);
    }

    /** @throws BankAccountNotFound */
    public function disallowForSubunits(int $id): void
    {
        $account = $this->bankAccounts->find($id);

        $account->disallowForSubunits();

        $this->detachBankAccountFromGroups($account, true);
        $this->detachBankAccountFromInvoiceSequences($account, true);

        $this->bankAccounts->save($account);
    }

    /** @throws BankAccountNotFound */
    public function countGroupsDetachedByDisallowForSubunits(int $id): int
    {
        $account = $this->bankAccounts->find($id);

        return count(
            array_filter(
                $this->groups->findByBankAccount($id),
                static fn (Group $group): bool => $group->getUnitIds() !== [$account->getUnitId()],
            ),
        );
    }

    /** @throws BankAccountNotFound */
    public function countInvoiceSequencesDetachedByDisallowForSubunits(int $id): int
    {
        $account = $this->bankAccounts->find($id);

        return count(
            array_filter(
                $this->invoiceSequences->findByBankAccount($id),
                static fn (\App\Model\Invoice\Entity\InvoiceSequence $sequence): bool => $sequence->getUnit() !== $account->getUnitId(),
            ),
        );
    }

    /** @throws BankAccountNotFound */
    public function countGroupsUsingBankAccount(int $id): int
    {
        $this->bankAccounts->find($id);

        return count($this->groups->findByBankAccount($id));
    }

    /** @throws BankAccountNotFound */
    public function countInvoiceSequencesUsingBankAccount(int $id): int
    {
        $this->bankAccounts->find($id);

        return count($this->invoiceSequences->findByBankAccount($id));
    }

    public function find(int $id): ?BankAccountDTO
    {
        try {
            return BankAccountFactory::create($this->bankAccounts->find($id));
        } catch (BankAccountNotFound) {
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
    public function findByIds(array $ids): array
    {
        Assert::thatAll($ids)->integer();
        $accounts = $this->bankAccounts->findByIds($ids);

        return array_map(
            function (BankAccount $a) {
                return BankAccountFactory::create($a);
            },
            $accounts,
        );
    }

    /** @return BankAccountDTO[] */
    public function findByUnit(UnitId $unitId): array
    {
        $accounts = $this->bankAccounts->findByUnit($this->unitResolver->getOfficialUnitId($unitId->toInt()));
        $accounts = array_filter(
            $accounts,
            function (BankAccount $a) use ($unitId) {
                return $a->getUnitId() === $unitId->toInt() || $a->isAllowedForSubunits();
            },
        );

        return array_map(
            function (BankAccount $a) {
                return BankAccountFactory::create($a);
            },
            $accounts,
        );
    }

    /**
     * @return Transaction[]
     *
     * @throws TokenNotSet
     * @throws BankTimeout
     * @throws BankTimeLimit
     * @throws BankWrongTokenAccount
     */
    public function getTransactions(int $bankAccountId, int $daysBack): array
    {
        Assert::that($daysBack)->greaterThan(0);

        return $this->transactions->getTransactions($bankAccountId, $daysBack);
    }

    /** @return list<BankTransaction> */
    public function getPersistentTransactions(int $bankAccountId, int $daysBack): array
    {
        Assert::that($daysBack)->greaterThan(0);

        $account = $this->bankAccounts->find($bankAccountId);
        $today = \Cake\Chronos\ChronosDate::today();

        return $this->transactions->getPersistentTransactionsForPeriod($account, $today->subDays($daysBack), $today);
    }

    /** @return list<BankTransactionImportBatch> */
    public function getImportBatches(int $bankAccountId, int $limit = 20): array
    {
        $account = $this->bankAccounts->find($bankAccountId);

        return $this->transactionImportBatches->findByBankAccount($account, $limit);
    }

    public function importGpcTransactions(int $bankAccountId, FileUpload $fileUpload, string $importedBy): BankTransactionImportBatch
    {
        if (! $fileUpload->isOk()) {
            throw new InvalidArgumentException('Vyskytla se chyba při nahrávání GPC souboru.');
        }

        return $this->transactions->importGpcTransactions(
            $bankAccountId,
            $fileUpload->getSanitizedName(),
            (string) $fileUpload->getContents(),
            $importedBy,
        );
    }

    /**
     * Import bank accounts from Skautis that don't already exist in DB.
     *
     * @return array{int, int, int} [importedCount, skautisCount, existingCount]
     *
     * @throws BankAccountNotFound when no new accounts to import
     */
    public function importFromSkautis(int $unitId): array
    {
        $resolvedUnitId = $this->unitResolver->getOfficialUnitId($unitId);
        $existingAccounts = $this->bankAccounts->findByUnit($resolvedUnitId);
        $existingNumbers = array_map(
            static fn (BankAccount $a): string => (string) $a->getNumber(),
            $existingAccounts,
        );

        $skautisAccounts = $this->importer->import($resolvedUnitId);

        $importable = array_filter(
            $skautisAccounts,
            static fn (AccountNumber $n): bool => ! in_array((string) $n, $existingNumbers, true),
        );

        $skautisCount = count($skautisAccounts);
        $existingCount = count($existingAccounts);
        $importableCount = count($importable);

        if ($importableCount === 0) {
            throw new BankAccountNotFound('Žádné nové účty k importu.');
        }

        $now = new DateTimeImmutable();
        $i = 1;
        foreach ($importable as $number) {
            $this->bankAccounts->save(
                new BankAccount($resolvedUnitId, 'Importovaný účet ('.$i++.')', $number, null, $now, $this->unitResolver),
            );
        }

        return [$importableCount, $skautisCount, $existingCount];
    }

    /**
     * Cleans cached transactions for account.
     */
    private function cleanFioCache(int $bankAccountId): void
    {
        $this->fioCache->clean([Cache::Tags => 'fio/'.$bankAccountId]);
    }

    private function detachBankAccountFromGroups(BankAccount $account, bool $subunitsOnly = false): void
    {
        foreach ($this->groups->findByBankAccount($account->getId()) as $group) {
            if ($subunitsOnly && $group->getUnitIds() === [$account->getUnitId()]) {
                continue;
            }

            $group->removeBankAccount();
            $this->groups->save($group);
        }
    }

    private function detachBankAccountFromInvoiceSequences(BankAccount $account, bool $subunitsOnly = false): void
    {
        foreach ($this->invoiceSequences->findByBankAccount($account->getId()) as $sequence) {
            if ($subunitsOnly && $sequence->getUnit() === $account->getUnitId()) {
                continue;
            }

            $sequence->setBankAccount(null);
            $this->invoiceSequences->save($sequence);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getCzechBankAccountCodes(): array
    {
        $codes = [];
        foreach ($this->bankAccountValidator->getValidBankCodes() as $key => $value) {
            $codes[(string) $key] = sprintf('%s - %s', $key, $value);
        }

        return $codes;
    }

    /**
     * @return array<string, string>
     */
    public function getCzechBankAccountNames(): array
    {
        return $this->bankAccountValidator->getValidBankCodes();
    }

    /**
     * @return array<string, string>
     */
    public function getCzechBankAccountBic(): array
    {
        return array_map(
            static fn (?string $bic): string => $bic ?? '',
            $this->bankAccountValidator->getBankBics(),
        );
    }

    /**
     * @throws BankNotFoundException
     */
    public function getBankInfo(string $bankCode): BankInfoDTO
    {
        return $this->bankAccountValidator->getBankInfo($bankCode);
    }
}
