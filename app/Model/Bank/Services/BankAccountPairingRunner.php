<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\PairingCandidate;
use App\Model\Utils\Arrays;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;

use function is_array;
use function is_int;

final class BankAccountPairingRunner
{
    public function __construct(
        private readonly BankPairingCandidateProvider $pairingCandidates,
        private readonly BankTransactionPairingService $transactionPairingService,
    ) {
    }

    /**
     * @param  list<mixed>                                                                    $scopeItems
     * @param  callable(mixed): (int|null)                                                    $bankAccountIdResolver
     * @param  callable(int, list<mixed>): (BankAccount|null)                                 $bankAccountResolver
     * @param  callable(list<mixed>): list<PairingCandidate>                                  $scopeCandidatesResolver
     * @param  callable(list<mixed>): ChronosDate                                             $pairSinceResolver
     * @param  callable(BankAccount, ChronosDate, ChronosDate): list<\App\Model\Bank\Entity\BankTransaction> $transactionsLoader
     * @param  callable(BankAccount, list<mixed>): bool|null                                  $canPairResolver
     * @return list<BankAccountPairingRunResult>
     */
    public function run(
        array $scopeItems,
        callable $bankAccountIdResolver,
        callable $bankAccountResolver,
        callable $scopeCandidatesResolver,
        callable $pairSinceResolver,
        callable $transactionsLoader,
        ?callable $canPairResolver = null,
        bool $skipWhenNoScopeCandidates = false,
    ): array {
        $scopeItemsByAccount = Arrays::groupBy($scopeItems, $bankAccountIdResolver, true);
        $now = ChronosDate::today();
        $results = [];

        foreach ($scopeItemsByAccount as $bankAccountId => $accountScopeItems) {
            if (! is_int($bankAccountId) || ! is_array($accountScopeItems)) {
                continue;
            }

            $bankAccount = $bankAccountResolver($bankAccountId, $accountScopeItems);
            if (! $bankAccount instanceof BankAccount) {
                continue;
            }

            if ($canPairResolver !== null && ! $canPairResolver($bankAccount, $accountScopeItems)) {
                continue;
            }

            $scopeCandidates = $scopeCandidatesResolver($accountScopeItems);
            if ($skipWhenNoScopeCandidates && $scopeCandidates === []) {
                continue;
            }

            $pairSince = $pairSinceResolver($accountScopeItems);
            $pairingResult = $this->transactionPairingService->pairAutomatically(
                $transactionsLoader($bankAccount, $pairSince, $now),
                $this->pairingCandidates->getDomainCandidatesForBankAccount($bankAccountId),
                $scopeCandidates,
                new DateTimeImmutable(),
            );

            $results[] = new BankAccountPairingRunResult(
                $bankAccount,
                $pairSince,
                $now,
                $accountScopeItems,
                $pairingResult['payments'],
                $pairingResult['invoices'],
            );
        }

        return $results;
    }
}
