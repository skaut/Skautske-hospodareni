<?php

namespace Model\Bank\Fio;

use DateTimeImmutable;
use FioApi\Downloader;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use FioApi\Transaction as ApiTransaction;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use Model\Payment\BankAccount;
use Model\Payment\Fio\IFioClient;
use Model\Payment\TokenNotSetException;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Nette\Utils\DateTime;
use Psr\Log\LoggerInterface;

class FioClient implements IFioClient
{

    /** @var ClientInterface */
    private $client;

    /** @var LoggerInterface */
    private $logger;


    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }


    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account): array
    {
        $token = $account->getToken();

        if($token === NULL) {
            throw new TokenNotSetException();
        }

        $transactions = $this->loadTransactionsFromApi(DateTime::from($since), DateTime::from($until), $token);
        $transactions = array_map([$this, 'createTransactionDTO'], $transactions);

        return array_reverse($transactions); // DESC sort
    }

    private function createTransactionDTO(ApiTransaction $transaction): Transaction
    {
        return new Transaction(
            (string)$transaction->getId(),
            \DateTimeImmutable::createFromMutable($transaction->getDate()),
            $transaction->getAmount(),
            $transaction->getSenderAccountNumber() . '/' . $transaction->getSenderBankCode(),
            $transaction->getUserIdentity() ?? $transaction->getPerformedBy() ?? '',
            $transaction->getVariableSymbol() !== NULL ? (int)$transaction->getVariableSymbol() : NULL,
            $transaction->getConstantSymbol() !== NULL ? (int)$transaction->getConstantSymbol() : NULL,
            $transaction->getComment()
        );
    }

    /**
     * @return ApiTransaction[]
     * @throws BankTimeLimitException
     * @throws BankTimeoutException
     */
    private function loadTransactionsFromApi(DateTime $since, DateTime $until, string $token): array
    {
        $api = new Downloader($token, $this->client);

        try {
            return $api->downloadFromTo($since, $until)->getTransactions();
        } catch (TooGreedyException $e) {
            throw new BankTimeLimitException('', 0, $e);
        } catch (BadResponseException | InternalErrorException $e) {
            throw new BankTimeoutException("There was an error when connecting to FIO", $e->getCode(), $e);
        }
    }

}
