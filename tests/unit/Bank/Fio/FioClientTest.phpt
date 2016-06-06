<?php

use Model\Bank\Fio\FioClient;
use Model\Bank\Fio\Transaction;
use Model\Bank\Http\Response;
use Tester\Assert;

require __DIR__.'/../../../bootstrap.php';

/**
 * @testCase
 */
class FioClientTest extends \Tester\TestCase
{

	public function tearDown()
	{
		\Mockery::close();
	}

	private function buildUrl($token, DateTime $since, DateTime $until)
	{
		$sinceString = $since->format('Y-m-d');
		$untilString = $until->format('Y-m-d');
		return "https://www.fio.cz/ib_api/rest/periods/$token/$sinceString/$untilString/transactions.json";
	}

	public function testGetTransactions()
	{
		$since = new DateTime('- 5 days');
		$until = new DateTime();
		$token = 'test-token';

		$expectedUrl = $this->buildUrl($token, $since, $until);
		$client = \Mockery::mock('Model\Bank\Http\IClient');
		$client->shouldReceive('get')
			->with($expectedUrl, 3)
			->andReturn(new Response(200, file_get_contents(__DIR__.'/response.json'), FALSE));

		$fio = new FioClient($client);

		$transactions = $fio->getTransactions($since, $until, $token);

		/* @var $transactions Transaction[] */
		Assert::count(2, $transactions);

		Assert::same('9786224406', $transactions[0]->getId());
		Assert::same('2016-06-01', $transactions[0]->getDate()->format('Y-m-d'));
		Assert::equal(2700.00, $transactions[0]->getAmount());
		Assert::same('123456789/0800', $transactions[0]->getBankAccount());
		Assert::same('Peter Parker', $transactions[0]->getName());
		Assert::same('1113', $transactions[0]->getVariableSymbol());
		Assert::null($transactions[0]->getConstantSymbol());

		Assert::same('9787642472', $transactions[1]->getId());
		Assert::same('2016-06-08', $transactions[1]->getDate()->format('Y-m-d'));
		Assert::equal(2000.00, $transactions[1]->getAmount());
		Assert::same('111111111/3030', $transactions[1]->getBankAccount());
		Assert::same('Peter Black', $transactions[1]->getName());
		Assert::same('123', $transactions[1]->getVariableSymbol());
		Assert::null($transactions[1]->getConstantSymbol());
	}

	/**
	 * @throws \Model\BankTimeoutException
	 */
	public function testTimeOut()
	{
		$since = new DateTime('- 5 days');
		$until = new DateTime();
		$token = 'test-token';

		$client = \Mockery::mock('Model\Bank\Http\IClient');
		$client->shouldReceive('get')
			->withAnyArgs()
			->andReturn(new Response(NULL, NULL, TRUE));

		$fio = new FioClient($client);

		$fio->getTransactions($since, $until, $token);
	}

	/**
	 * @throws \Model\BankTimeLimitException
	 */
	public function testOverloadedApi()
	{
		$since = new DateTime('- 5 days');
		$until = new DateTime();
		$token = 'test-token';

		$client = \Mockery::mock('Model\Bank\Http\IClient');
		$client->shouldReceive('get')
			->withAnyArgs()
			->andReturn(new Response(409, NULL, FALSE));

		$fio = new FioClient($client);

		$fio->getTransactions($since, $until, $token);
	}
}
(new FioClientTest())->run();