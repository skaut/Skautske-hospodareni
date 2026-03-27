<?php

declare(strict_types=1);

namespace App\Model\Bank\IntegrationTests;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionImportBatch;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Manager\BankTransactionImportManager;
use App\Model\Bank\Repository\BankTransactionImportBatchRepository;
use App\Model\Bank\Repository\BankTransactionRepository;
use App\Model\Bank\Services\BankTransactionKeyGenerator;
use App\Model\Bank\Services\BankTransactionService;
use App\Model\Bank\Services\GpcParser;
use App\Model\Infrastructure\Repositories\Payment\BankAccountRepository as LegacyBankAccountRepository;
use App\Model\Payment\Fio\IFioClient;
use App\Model\Payment\IUnitResolver;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use InvalidArgumentException;
use Mockery as m;

final class BankTransactionServiceTest extends IntegrationTest
{
    private BankTransactionService $service;

    private LegacyBankAccountRepository $bankAccounts;

    private BankTransactionRepository $bankTransactions;

    private BankTransactionImportBatchRepository $importBatches;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            BankAccount::class,
            BankTransaction::class,
            BankTransactionImportBatch::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->bankAccounts = new LegacyBankAccountRepository($this->entityManager);
        $this->bankTransactions = new BankTransactionRepository($this->entityManager);
        $this->importBatches = new BankTransactionImportBatchRepository($this->entityManager);
        $this->service = new BankTransactionService(
            $this->bankAccounts,
            $this->bankTransactions,
            new BankTransactionImportManager($this->entityManager, $this->bankTransactions),
            m::mock(IFioClient::class),
            new GpcParser(),
            new BankTransactionKeyGenerator(),
        );
    }

    public function testRepeatedImportDoesNotCreateDuplicateTransactions(): void
    {
        $account = new BankAccount(
            1,
            'GPC účet',
            Helpers::createAccountNumber(),
            null,
            new DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => 1]),
            BankTransactionSource::GPC,
        );
        $this->bankAccounts->save($account);

        $contents = str_replace(
            '0000008310192897',
            '0000002000942144',
            (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc'),
        );
        self::assertNotSame('', $contents);
        self::assertCount(
            1,
            (new GpcParser())->parse(
                $account->getNumber()->getNumberWithPrefixAndBankCode(),
                $contents,
                new BankTransactionKeyGenerator(),
            ),
        );

        $firstBatch = $this->service->importGpcTransactions($account->getId(), 'sample.gpc', $contents, 'Tester');
        $secondBatch = $this->service->importGpcTransactions($account->getId(), 'sample.gpc', $contents, 'Tester');

        self::assertSame(1, $firstBatch->getTransactionCount());
        self::assertSame(1, $firstBatch->getNewTransactionCount());
        self::assertSame(1, $secondBatch->getTransactionCount());
        self::assertSame(0, $secondBatch->getNewTransactionCount());
        self::assertTrue($this->service->hasTransactions($account));

        $transactions = $this->bankTransactions->findByAccountAndDateRange(
            $account,
            new DateTimeImmutable('2026-01-01 00:00:00'),
            new DateTimeImmutable('2026-12-31 23:59:59'),
        );
        self::assertCount(1, $transactions);
        self::assertSame(BankTransactionSource::GPC, $transactions[0]->getSource());

        $batches = $this->importBatches->findByBankAccount($account, 10);
        self::assertCount(2, $batches);
        self::assertSame([0, 1], array_map(
            static fn (BankTransactionImportBatch $batch): int => $batch->getNewTransactionCount(),
            $batches,
        ));
        self::assertSame([1, 1], array_map(
            static fn (BankTransactionImportBatch $batch): int => $batch->getTransactionCount(),
            $batches,
        ));
    }

    public function testRejectsImportForDifferentBankAccountThanStatementHeader(): void
    {
        $account = new BankAccount(
            1,
            'Jiny GPC ucet',
            new \App\Model\Common\Embeddable\AccountNumber('19', '17608231', '0100'),
            null,
            new DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => 1]),
            BankTransactionSource::GPC,
        );
        $this->bankAccounts->save($account);

        $contents = (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GPC soubor patri k uctu 8310192897 a nelze jej importovat k uctu 19-17608231/0100.');

        $this->service->importGpcTransactions($account->getId(), 'sample.gpc', $contents, 'Tester');
    }
}
