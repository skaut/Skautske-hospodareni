<?php

namespace Bank\Fio;

use GuzzleHttp\ClientInterface;
use Mockery as m;

use Model\Bank\Fio\FioClient;
use Model\Payment\BankAccount;
use Model\Payment\TokenNotSetException;
use Psr\Log\NullLogger;

class FioClientTest extends \Codeception\Test\Unit
{

    public function testBankAccountWithoutTokenThrowsException()
    {
        $fio = new FioClient(m::mock(ClientInterface::class), new NullLogger());

        $this->expectException(TokenNotSetException::class);

        $fio->getTransactions(new \DateTimeImmutable(), new \DateTimeImmutable(), m::mock(BankAccount::class, ['getToken' => NULL]));
    }

    protected function _after()
    {
        m::close();
    }

}
