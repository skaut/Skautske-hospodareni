<?php

namespace Bank\Fio;

use Mockery as m;

use Model\Bank\Fio\FioClient;
use Model\Bank\Fio\Transaction;
use Model\Bank\Http\IClient;
use Model\Bank\Http\Response;
use DateTime;
use Model\Payment\BankAccount;
use Model\Payment\TokenNotSetException;
use Psr\Log\NullLogger;

class FioClientTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testGetTransactions()
    {
        $since = new DateTime('- 5 days');
        $until = new DateTime();
        $token = 'test-token';

        $expectedUrl = $this->buildUrl($token, $since, $until);
        $client = m::mock('Model\Bank\Http\IClient');
        $client->shouldReceive('get')
            ->with($expectedUrl, 3)
            ->andReturn(new Response(200, file_get_contents(__DIR__ . '/response.json'), FALSE));

        $fio = new FioClient($client, new NullLogger());

        $transactions = $fio->getTransactions($since, $until, m::mock(BankAccount::class, ['getToken' => $token]));

        /* @var $transactions Transaction[] */
        $this->assertCount(2, $transactions);

        $this->assertSame(9786224406, $transactions[0]->getId());
        $this->assertSame('2016-06-01', $transactions[0]->getDate()->format('Y-m-d'));
        $this->assertEquals(2700.00, $transactions[0]->getAmount());
        $this->assertSame('123456789/0800', $transactions[0]->getBankAccount());
        $this->assertSame('Peter Parker', $transactions[0]->getName());
        $this->assertSame(1113, $transactions[0]->getVariableSymbol());
        $this->assertNull($transactions[0]->getConstantSymbol());

        $this->assertSame(9787642472, $transactions[1]->getId());
        $this->assertSame('2016-06-08', $transactions[1]->getDate()->format('Y-m-d'));
        $this->assertEquals(2000.00, $transactions[1]->getAmount());
        $this->assertSame('111111111/3030', $transactions[1]->getBankAccount());
        $this->assertSame('Peter Black', $transactions[1]->getName());
        $this->assertSame(123, $transactions[1]->getVariableSymbol());
        $this->assertNull($transactions[1]->getConstantSymbol());
    }

    public function testTimeOutThrowsException()
    {
        $since = new DateTime('- 5 days');
        $until = new DateTime();
        $token = 'test-token';

        $client = m::mock(IClient::class);
        $client->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(new Response(NULL, NULL, TRUE));

        $fio = new FioClient($client, new NullLogger());

        $this->expectException(\Model\BankTimeoutException::class);
        $fio->getTransactions($since, $until, m::mock(BankAccount::class, ['getToken' => $token]));
    }

    public function testOverloadedApiThrowsException()
    {
        $since = new DateTime('- 5 days');
        $until = new DateTime();
        $token = 'test-token';

        $client = m::mock(IClient::class);
        $client->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(new Response(409, NULL, FALSE));

        $this->expectException(\Model\BankTimeLimitException::class);
        $fio = new FioClient($client, new NullLogger());

        $fio->getTransactions($since, $until, m::mock(BankAccount::class, ['getToken' => $token]));
    }

    public function testBankAccountWithoutTokenThrowsException()
    {
        $fio = new FioClient(m::mock(IClient::class), new NullLogger());

        $this->expectException(TokenNotSetException::class);

        $fio->getTransactions(new DateTime(), new DateTime(), m::mock(BankAccount::class, ['getToken' => NULL]));
    }

    private function buildUrl($token, DateTime $since, DateTime $until)
    {
        $sinceString = $since->format('Y-m-d');
        $untilString = $until->format('Y-m-d');
        return "https://www.fio.cz/ib_api/rest/periods/$token/$sinceString/$untilString/transactions.json";
    }

    protected function _after()
    {
        m::close();
    }

}
