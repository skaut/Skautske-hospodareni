<?php

declare(strict_types=1);

namespace App\Model\Bank;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Exception\BankWrongTokenAccount;
use App\Model\Bank\Services\BankAccountPairingRunner;
use App\Model\Bank\Services\BankPairingCandidateProvider;
use App\Model\Bank\Services\BankTransactionService;
use App\Model\DTO\Payment\PairingResult;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Group;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Assert\Assert;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;

use function array_map;
use function array_values;
use function min;

class BankService
{
    public const DAYS_BACK_DEFAULT = 60;

    public function __construct(
        private IGroupRepository $groups,
        private BankTransactionService $transactions,
        private IPaymentRepository $payments,
        private IBankAccountRepository $bankAccounts,
        private BankPairingCandidateProvider $pairingCandidates,
        private BankAccountPairingRunner $pairingRunner,
    ) {
    }

    /**
     * Completes payments from info on bank account(s).
     *
     * @param int[] $groupIds
     *
     * @return PairingResult[] Description of paired payments
     *
     * @throws BankTimeLimit
     * @throws BankTimeout
     * @throws BankWrongTokenAccount
     * @throws InvalidOAuth
     */
    public function pairAllGroups(array $groupIds, ?int $daysBack = null): array
    {
        Assert::thatAll($groupIds)->integer();
        Assert::that($daysBack)->nullOr()->min(1);

        $foundGroups = $this->groups->findByIds($groupIds);

        $pairingResults = [];

        foreach ($this->pairingRunner->run(
            $foundGroups,
            static fn (Group $group): ?int => $group->getBankAccountId(),
            fn (int $bankAccountId): Entity\BankAccount => $this->bankAccounts->find($bankAccountId),
            fn (array $groups): array => $this->pairingCandidates->getScopedCandidatesForGroups(
                array_values(array_map(static fn (Group $group): int => $group->getId(), $groups)),
            ),
            fn (array $groups): ChronosDate => $daysBack === null
                ? $this->resolvePairingIntervalStart($groups)
                : ChronosDate::today()->subDays($daysBack),
            fn (Entity\BankAccount $bankAccount, ChronosDate $pairSince, ChronosDate $now): array => $this->transactions->getPersistentTransactionsForPeriod($bankAccount, $pairSince, $now),
            fn (BankAccount $bankAccount, array $groups): bool => ! (
                $bankAccount->getTransactionSource()->value === BankTransactionSource::FIO->value
                && $bankAccount->getToken() === null
            ) && $this->payments->findByMultipleGroups(array_values(array_map(static fn (Group $group): int => $group->getId(), $groups))) !== [],
        ) as $result) {
            $this->payments->saveMany($result->payments);
            $pairingResults[] = new PairingResult(
                $result->bankAccount->getName(),
                $result->pairSince,
                $result->pairedUntil,
                $result->getPairedPaymentsCount(),
            );

            if ($daysBack === null) {
                $this->updateLastPairing($result->scopeItems, $result->pairedUntil->toNative());
            }
        }

        return $pairingResults;
    }

    /** @param Group[] $groups */
    private function updateLastPairing(array $groups, DateTimeImmutable $time): void
    {
        foreach ($groups as $group) {
            $group->updateLastPairing($time);
            $this->groups->save($group);
        }
    }

    /** @param Group[] $groups */
    private function resolvePairingIntervalStart(array $groups): ChronosDate
    {
        $defaultStart = ChronosDate::today()->subDays(self::DAYS_BACK_DEFAULT);

        if ($groups === []) {
            return $defaultStart;
        }

        return new ChronosDate(
            min(
                array_map(fn (Group $g) => $g->getLastPairing() ?? $defaultStart, $groups),
            ),
        );
    }
}
