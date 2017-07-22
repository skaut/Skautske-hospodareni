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
        if($account->getToken() === NULL) {
            throw new TokenNotSetException();
        }

        $api = new Downloader($account->getToken(), $this->client);

        try {
            return array_map(function (ApiTransaction $t) {
                return new Transaction(
                    (string)$t->getId(),
                    \DateTimeImmutable::createFromMutable($t->getDate()),
                    $t->getAmount(),
                    $t->getSenderAccountNumber() . '/' . $t->getSenderBankCode(),
                    $t->getUserIdentity() ?? $t->getPerformedBy() ?? '',
                    $t->getVariableSymbol() !== NULL ? (int)$t->getVariableSymbol() : NULL,
                    $t->getConstantSymbol() !== NULL ? (int)$t->getConstantSymbol() : NULL,
                    $t->getComment()
                );
            }, $api->downloadFromTo(DateTime::from($since), DateTime::from($until))->getTransactions());
        } catch (TooGreedyException $e) {
            throw new BankTimeLimitException();
        } catch (BadResponseException | InternalErrorException $e) {
            throw new BankTimeoutException("There was an error when connecting to FIO", $e->getCode(), $e);
        }
    }

}
