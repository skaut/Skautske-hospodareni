<?php

declare(strict_types=1);

namespace Model\Bank\Fio;

use Codeception\Test\Unit;
use FioApi\Downloader;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use GuzzleHttp\Exception\TransferException;
use Mockery as m;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Model\Payment\BankAccount;
use Model\Payment\TokenNotSetException;
use Psr\Log\NullLogger;

class FioClientTest extends Unit
{
    public function testBankAccountWithoutTokenThrowsException() : void
    {
        $factory = m::mock(IDownloaderFactory::class);
        $fio     = new FioClient($factory, new NullLogger());

        $this->expectException(TokenNotSetException::class);

        $fio->getTransactions(
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->mockAccount(null)
        );
    }

    public function testTooGreedyExceptionResultsInLimitException() : void
    {
        $since = new \DateTimeImmutable();
        $until = new \DateTimeImmutable();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since, $until)
            ->once()
            ->andThrow(TooGreedyException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio     = new FioClient($factory, new NullLogger());

        $this->expectException(BankTimeLimitException::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    public function tesGeneralApiErrorExceptionResultsInTimeoutException() : void
    {
        $since = new \DateTimeImmutable();
        $until = new \DateTimeImmutable();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since, $until)
            ->once()
            ->andThrow(TransferException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio     = new FioClient($factory, new NullLogger());

        $this->expectException(BankTimeoutException::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    public function testInternalErrorResultsInTimeoutException() : void
    {
        $since = new \DateTimeImmutable();
        $until = new \DateTimeImmutable();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since, $until)
            ->once()
            ->andThrow(InternalErrorException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio     = new FioClient($factory, new NullLogger());

        $this->expectException(BankTimeoutException::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    private function mockAccount(?string $token = 'token') : BankAccount
    {
        return m::mock(BankAccount::class, [
            'getId' => 10,
            'getToken' => $token,
        ]);
    }

    private function buildDownloaderFactory(Downloader $downloader) : IDownloaderFactory
    {
        $factory = m::mock(IDownloaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token')
            ->andReturn($downloader);

        return $factory;
    }
}
