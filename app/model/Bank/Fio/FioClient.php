<?php

declare(strict_types=1);

namespace Model\Bank\Fio;

use DateTimeImmutable;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use FioApi\Transaction as ApiTransaction;
use GuzzleHttp\Exception\TransferException;
use Model\BankTimeLimit;
use Model\BankTimeout;
use Model\Payment\BankAccount;
use Model\Payment\Fio\IFioClient;
use Model\Payment\TokenNotSet;
use Psr\Log\LoggerInterface;
use function array_map;
use function array_reverse;
use function sprintf;

class FioClient implements IFioClient
{
    private IDownloaderFactory $downloaderFactory;

    private LoggerInterface $logger;

    public function __construct(IDownloaderFactory $downloaderFactory, LoggerInterface $logger)
    {
        $this->downloaderFactory = $downloaderFactory;
        $this->logger            = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account) : array
    {
        if ($account->getToken() === null) {
            throw new TokenNotSet();
        }

        $transactions = $this->loadTransactionsFromApi($since, $until, $account);
        $transactions = array_map(
            function (ApiTransaction $transaction) {
                return new Transaction(
                    $transaction->getId(),
                    $transaction->getDate(),
                    $transaction->getAmount(),
                    $transaction->getSenderAccountNumber() . '/' . $transaction->getSenderBankCode(),
                    $transaction->getUserIdentity() ?? $transaction->getPerformedBy() ?? '',
                    $transaction->getVariableSymbol() !== null ? (int) $transaction->getVariableSymbol() : null,
                    $transaction->getConstantSymbol() !== null ? (int) $transaction->getConstantSymbol() : null,
                    $transaction->getComment()
                );
            },
            $transactions
        );

        return array_reverse($transactions); // DESC sort
    }

    /**
     * @return ApiTransaction[]
     *
     * @throws BankTimeLimit
     * @throws BankTimeout
     */
    private function loadTransactionsFromApi(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account) : array
    {
        $api = $this->downloaderFactory->create($account->getToken());

        try {
            return $api->downloadFromTo($since, $until)->getTransactions();
        } catch (TooGreedyException $e) {
            $this->logger->warning('Bank account #' . $account->getId() . ' hit API limit');

            throw new BankTimeLimit('', 0, $e);
        } catch (TransferException | InternalErrorException $e) {
            $this->logger->warning(
                sprintf('Bank account #%d request failed: %s', $account->getId(), $e->getMessage()),
                ['previous' => $e]
            );

            throw new BankTimeout('There was an error when connecting to FIO', $e->getCode(), $e);
        }
    }
}
