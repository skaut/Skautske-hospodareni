<?php

namespace Model\Bank\Fio;

use DateTimeImmutable;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use FioApi\Transaction as ApiTransaction;
use GuzzleHttp\Exception\TransferException;
use Model\Payment\BankAccount;
use Model\Payment\Fio\IFioClient;
use Model\Payment\TokenNotSetException;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Psr\Log\LoggerInterface;

class FioClient implements IFioClient
{

    /** @var IDownloaderFactory */
    private $downloaderFactory;

    /** @var LoggerInterface */
    private $logger;


    public function __construct(IDownloaderFactory $downloaderFactory, LoggerInterface $logger)
    {
        $this->downloaderFactory = $downloaderFactory;
        $this->logger = $logger;
    }


    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account): array
    {
        if($account->getToken() === NULL) {
            throw new TokenNotSetException();
        }

        $transactions = $this->loadTransactionsFromApi($since, $until, $account);
        $transactions = array_map([$this, 'createTransactionDTO'], $transactions);

        return array_reverse($transactions); // DESC sort
    }

    private function createTransactionDTO(ApiTransaction $transaction): Transaction
    {
        return new Transaction(
            $transaction->getId(),
            $transaction->getDate(),
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
    private function loadTransactionsFromApi(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account): array
    {
        $api = $this->downloaderFactory->create($account->getToken());

        try {
            return $api->downloadFromTo($since, $until)->getTransactions();
        } catch (TooGreedyException $e) {
            $this->logger->error("Bank account #{$account->getId()} hit API limit");
            throw new BankTimeLimitException('', 0, $e);
        } catch (TransferException | InternalErrorException $e) {
            $this->logger->error(
                sprintf('Bank account #%d request failed: %s', $account->getId(), $e->getMessage()),
                ['previous' => $e]
            );

            throw new BankTimeoutException("There was an error when connecting to FIO", $e->getCode(), $e);
        }
    }

}
