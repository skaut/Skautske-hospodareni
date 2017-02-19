<?php

namespace Model\Bank\Fio;

use Model\Bank\Http\IClient;
use Nette;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;

class FioClient extends Nette\Object
{

    const ID = 'column22';
    const DATE = 'column0';
    const AMOUNT = 'column1';
    const ACCOUNT = 'column2';
    const BANK_ID = 'column3';
    const NAME = 'column7';
    const USER = 'column10';
    const VARIABLE_SYMBOL = 'column5';
    const CONSTANT_SYMBOL = 'column4';
    const NOTE = 'column16';


    const REQUEST_TIMEOUT = 3;
    const FIO_URL = 'https://www.fio.cz/ib_api/rest/periods/:token/:since/:until/transactions.json';

    /** @var IClient */
    private $http;

    /**
     * FioClient constructor.
     * @param IClient $http
     */
    public function __construct(IClient $http)
    {
        $this->http = $http;
    }

    /**
     * @param \DateTime $since
     * @param \DateTime $until
     * @param string $token
     * @return Transaction[]
     */
    public function getTransactions(\DateTime $since, \DateTime $until, $token)
    {
        $url = strtr(self::FIO_URL, [
            ':token' => $token,
            ':since' => $since->format('Y-m-d'),
            ':until' => $until->format('Y-m-d')
        ]);

        $response = $this->performRequest($url);
        $transactions = [];

        $transactionList = $response['accountStatement']['transactionList'];

        if ($transactionList !== NULL) {
            foreach ($transactionList['transaction'] as $transaction) {
                $bankAccount = $this->getValue($transaction, self::ACCOUNT);

                $bankAccount = $bankAccount
                    ? $bankAccount . '/' . $this->getValue($transaction, self::BANK_ID)
                    : NULL;

                $transactions[] = new Transaction(
                    (string)$this->getValue($transaction, self::ID),
                    \DateTime::createFromFormat('Y-m-dO', $this->getValue($transaction, self::DATE)),
                    $this->getValue($transaction, self::AMOUNT),
                    $bankAccount,
                    $this->getValue($transaction, self::NAME) ?: $this->getValue($transaction, self::USER),
                    $this->getValue($transaction, self::VARIABLE_SYMBOL),
                    $this->getValue($transaction, self::CONSTANT_SYMBOL),
                    $this->getValue($transaction, self::NOTE)
                );
            }
        }

        return $transactions;
    }

    /**
     * @param array $transaction
     * @param $column
     * @return mixed
     */
    private function getValue(array $transaction, $column)
    {
        return isset($transaction[$column])
            ? $transaction[$column]['value']
            : NULL;
    }

    /**
     * @param string $url
     * @return array
     * @throws BankTimeLimitException
     * @throws BankTimeoutException
     */
    private function performRequest($url)
    {
        $response = $this->http->get($url, self::REQUEST_TIMEOUT);
        if ($response->isTimeout()) {
            throw new BankTimeoutException();
        }
        if ($response->getCode() == 409) {
            throw new BankTimeLimitException();
        }
        return json_decode($response->getBody(), TRUE);
    }

}
