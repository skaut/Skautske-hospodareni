<?php

declare(strict_types=1);

namespace Tests\Unit\Bank\Services;

use App\Model\Bank\Fio\IDownloaderFactory;
use App\Model\Bank\Services\FioTokenValidator;
use App\Model\Common\Embeddable\AccountNumber;
use Codeception\Test\Unit;
use DateTimeInterface;
use Exception;
use FioApi\Download\Downloader;
use FioApi\Download\Entity\TransactionList;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use InvalidArgumentException;
use Mockery as m;
use stdClass;

final class FioTokenValidatorTest extends Unit
{
    public function testValidTokenForAccountPasses(): void
    {
        $validator = new FioTokenValidator($this->factoryReturning($this->transactionList('2500548792', '2010')));

        $validator->validate(new AccountNumber(null, '2500548792', '2010'), 'token');

        self::assertTrue(true);
    }

    public function testTokenForDifferentAccountFails(): void
    {
        $validator = new FioTokenValidator($this->factoryReturning($this->transactionList('1234567890', '2010')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zadaný Fio token patří k účtu 1234567890/2010, ale ukládaný bankovní účet je 2500548792/2010.');

        $validator->validate(new AccountNumber(null, '2500548792', '2010'), 'token');
    }

    public function testInvalidOrInactiveTokenFailsWithUserMessage(): void
    {
        $validator = new FioTokenValidator($this->factoryThrowing(new InternalErrorException()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fio token není platný nebo ještě není aktivní.');

        $validator->validate(new AccountNumber(null, '2500548792', '2010'), 'token');
    }

    public function testTooFrequentTokenCheckFailsWithUserMessage(): void
    {
        $validator = new FioTokenValidator($this->factoryThrowing(new TooGreedyException()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fio dovoluje dotaz se stejným tokenem nejvýše jednou za 30 sekund.');

        $validator->validate(new AccountNumber(null, '2500548792', '2010'), 'token');
    }

    private function factoryReturning(TransactionList $transactionList): IDownloaderFactory
    {
        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->once()
            ->with(m::type(DateTimeInterface::class), m::type(DateTimeInterface::class))
            ->andReturn($transactionList);

        $factory = m::mock(IDownloaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token')
            ->andReturn($downloader);

        return $factory;
    }

    private function factoryThrowing(Exception $exception): IDownloaderFactory
    {
        $downloader = m::mock(Downloader::class);
        $downloader->shouldReceive('downloadFromTo')
            ->once()
            ->with(m::type(DateTimeInterface::class), m::type(DateTimeInterface::class))
            ->andThrow($exception);

        $factory = m::mock(IDownloaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token')
            ->andReturn($downloader);

        return $factory;
    }

    private function transactionList(string $accountNumber, string $bankCode): TransactionList
    {
        $data = new stdClass();
        $data->info = (object) [
            'accountId' => $accountNumber,
            'bankId' => $bankCode,
            'currency' => 'CZK',
            'iban' => '',
            'bic' => '',
            'openingBalance' => 0.0,
            'closingBalance' => 0.0,
            'dateStart' => '2026-07-13',
            'dateEnd' => '2026-07-13',
            'idFrom' => null,
            'idTo' => null,
            'idLastDownload' => null,
        ];
        $data->transactionList = (object) [
            'transaction' => [],
        ];

        return TransactionList::create($data);
    }
}
