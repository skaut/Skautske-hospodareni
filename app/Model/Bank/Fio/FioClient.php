<?php

declare(strict_types=1);

namespace App\Model\Bank\Fio;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Exception\BankWrongTokenAccount;
use App\Model\Bank\Services\BankTransactionKeyGenerator;
use App\Model\Bank\Transaction as BankTransaction;
use App\Model\Payment\Fio\IFioClient;
use App\Model\Payment\TokenNotSet;
use Cake\Chronos\ChronosDate;
use FioApi\Download\Entity\Transaction as ApiTransaction;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\TooGreedyException;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

use function array_map;
use function array_reverse;
use function sprintf;

use const PHP_EOL;

class FioClient implements IFioClient
{
    public function __construct(
        private IDownloaderFactory $downloaderFactory,
        private LoggerInterface $logger,
        private BankTransactionKeyGenerator $transactionKeyGenerator,
    ) {
    }

    public function get(): void
    {
        // TODO
        /*
         * Vytvorit tabulku s nastavení účtů a plateb pro automatické párování, nebudou se vždy párovat všechny účtu a všechny platby
         * Pokud bude mít uživatel možnost nastavit čas párování, pak je potřeba zadat jej do nastavení a zohlednit při stahování z banky
         * Stáhnout platby všech nastavených účtů
         * Provést párování a zapsat do dané platby, kolik plateb bylo kdy spárováno. Toto číslo vypsat uživateli
         * Přesunout do obecné servisy, aby bylo možné případně napojit i jiné banky.
         */
        echo 'GET'.PHP_EOL;
    }

    public function getTransactions(ChronosDate $since, ChronosDate $until, BankAccount $account): array
    {
        if ($account->getToken() === null) {
            throw new TokenNotSet();
        }

        $transactions = $this->loadTransactionsFromApi($since, $until, $account);
        $transactions = array_map(
            function (ApiTransaction $transaction) {
                return new BankTransaction(
                    $this->transactionKeyGenerator->fromFio($transaction->getId()),
                    BankTransactionSource::FIO,
                    $transaction->getDate(),
                    $transaction->getAmount(),
                    $transaction->getSenderAccountNumber().'/'.$transaction->getSenderBankCode(),
                    $transaction->getUserIdentity() ?? $transaction->getPerformedBy() ?? '',
                    $transaction->getVariableSymbol() !== null ? (int) $transaction->getVariableSymbol() : null,
                    $transaction->getConstantSymbol() !== null ? (int) $transaction->getConstantSymbol() : null,
                    $transaction->getComment(),
                    $transaction->getId(),
                );
            },
            $transactions,
        );

        return array_reverse($transactions); // DESC sort
    }

    /**
     * @return ApiTransaction[]
     *
     * @throws BankTimeLimit
     * @throws BankTimeout
     * @throws BankWrongTokenAccount
     */
    private function loadTransactionsFromApi(ChronosDate $since, ChronosDate $until, BankAccount $account): array
    {
        $api = $this->downloaderFactory->create($account->getToken());

        try {
            $list = $api->downloadFromTo($since->toNative(), $until->toNative());

            $intendedAccount = $account->getNumber()->getNumber().'/'.$account->getNumber()->getBankCode();
            $tokenAccount = $list->getAccount()->getAccountNumber().'/'.$list->getAccount()->getBankCode();
            if ($intendedAccount !== $tokenAccount) {
                $this->logger->warning('API token for wrong account. Bank account is '.$intendedAccount.', token is for account '.$tokenAccount.'.');

                throw new BankWrongTokenAccount($intendedAccount, $tokenAccount);
            }

            return $list->getTransactions();
        } catch (TooGreedyException $e) {
            $this->logger->warning('Bank account #'.$account->getId().' hit API limit');

            throw new BankTimeLimit('', 0, $e);
        } catch (TransferException|InternalErrorException $e) {
            $this->logger->warning(
                sprintf('Bank account #%d request failed: %s', $account->getId(), $e->getMessage()),
                ['previous' => $e],
            );

            throw new BankTimeout('There was an error when connecting to FIO', $e->getCode(), $e);
        }
    }
}
