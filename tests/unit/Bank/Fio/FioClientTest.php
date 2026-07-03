<?php

declare(strict_types=1);

namespace App\Model\Bank\Fio;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Services\BankTransactionKeyGenerator;
use App\Model\Payment\TokenNotSet;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use FioApi\Download\Downloader;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use GuzzleHttp\Exception\TransferException;
use Mockery as m;
use Psr\Log\NullLogger;

class FioClientTest extends Unit
{
    public function testBankAccountWithoutTokenThrowsException(): void
    {
        $factory = m::mock(IDownloaderFactory::class);
        $fio = new FioClient($factory, new NullLogger(), new BankTransactionKeyGenerator());

        $this->expectException(TokenNotSet::class);

        $fio->getTransactions(
            ChronosDate::today(),
            ChronosDate::today(),
            $this->mockAccount(null),
        );
    }

    public function testTooGreedyExceptionResultsInLimitException(): void
    {
        $since = ChronosDate::today();
        $until = ChronosDate::today();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since->toNative(), $until->toNative())
            ->once()
            ->andThrow(TooGreedyException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio = new FioClient($factory, new NullLogger(), new BankTransactionKeyGenerator());

        $this->expectException(BankTimeLimit::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    public function tesGeneralApiErrorExceptionResultsInTimeoutException(): void
    {
        $since = ChronosDate::today();
        $until = ChronosDate::today();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since->toNative(), $until->toNative())
            ->once()
            ->andThrow(TransferException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio = new FioClient($factory, new NullLogger(), new BankTransactionKeyGenerator());

        $this->expectException(BankTimeout::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    public function testInternalErrorResultsInTimeoutException(): void
    {
        $since = ChronosDate::today();
        $until = ChronosDate::today();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since->toNative(), $until->toNative())
            ->once()
            ->andThrow(InternalErrorException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio = new FioClient($factory, new NullLogger(), new BankTransactionKeyGenerator());

        $this->expectException(BankTimeout::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    private function mockAccount(?string $token = 'token'): BankAccount
    {
        return m::mock(BankAccount::class, [
            'getId' => 10,
            'getToken' => $token,
        ]);
    }

    private function buildDownloaderFactory(Downloader $downloader): IDownloaderFactory
    {
        $factory = m::mock(IDownloaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token')
            ->andReturn($downloader);

        return $factory;
    }
}
