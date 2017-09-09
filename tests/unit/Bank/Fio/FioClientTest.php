<?php

namespace Model\Bank\Fio;

use FioApi\Account;
use FioApi\Downloader;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use FioApi\TransactionList;
use GuzzleHttp\Exception\TransferException;
use Mockery as m;

use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Model\Payment\BankAccount;
use Model\Payment\TokenNotSetException;
use Psr\Log\NullLogger;

class FioClientTest extends \Codeception\Test\Unit
{

    public function testBankAccountWithoutTokenThrowsException()
    {
        $factory = m::mock(IDownloaderFactory::class);
        $fio = new FioClient($factory, new NullLogger());

        $this->expectException(TokenNotSetException::class);

        $fio->getTransactions(
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->mockAccount(NULL)
        );
    }

    public function testTooGreedyExceptionResultsInLimitException()
    {
        $since = new \DateTimeImmutable();
        $until = new \DateTimeImmutable();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since, $until)
            ->once()
            ->andThrow(TooGreedyException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio = new FioClient($factory, new NullLogger());

        $this->expectException(BankTimeLimitException::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    public function tesGeneralApiErrorExceptionResultsInTimeoutException()
    {
        $since = new \DateTimeImmutable();
        $until = new \DateTimeImmutable();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since, $until)
            ->once()
            ->andThrow(TransferException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio = new FioClient($factory, new NullLogger());

        $this->expectException(BankTimeoutException::class);

        $fio->getTransactions($since, $until, $this->mockAccount());
    }

    public function testInternalErrorResultsInTimeoutException()
    {
        $since = new \DateTimeImmutable();
        $until = new \DateTimeImmutable();

        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->with($since, $until)
            ->once()
            ->andThrow(InternalErrorException::class);

        $factory = $this->buildDownloaderFactory($downloader);
        $fio = new FioClient($factory, new NullLogger());

        $this->expectException(BankTimeoutException::class);

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
