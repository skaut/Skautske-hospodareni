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
use App\Model\Payment\BankAccountNotFound;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Assert\Assert;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function array_filter;
use function array_flip;
use function array_map;
use function array_sum;
use function array_values;
use function count;
use function max;
use function min;
use function sprintf;

use const ARRAY_FILTER_USE_KEY;

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
        private LoggerInterface $logger,
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
            fn (int $bankAccountId): BankAccount => $this->bankAccounts->find($bankAccountId),
            fn (array $groups): array => $this->pairingCandidates->getScopedCandidatesForGroups(
                array_values(array_map(static fn (Group $group): int => (int) $group->getId(), $groups)),
            ),
            fn (array $groups): ChronosDate => $daysBack === null
                ? $this->resolvePairingIntervalStart($groups)
                : ChronosDate::today()->subDays($daysBack),
            fn (BankAccount $bankAccount, ChronosDate $pairSince, ChronosDate $now): array => $this->transactions->getPersistentTransactionsForPeriod($bankAccount, $pairSince, $now),
            static fn (BankAccount $bankAccount): bool => ! (
                $bankAccount->getTransactionSource() === BankTransactionSource::FIO
                && $bankAccount->getToken() === null
            ),
            // Without an unpaid payment there is nothing to pair, so the bank is not even asked.
            skipWhenNoScopeCandidates: true,
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

    /**
     * Pair groups that have automatic pairing enabled (cron).
     *
     * Only FIO accounts take part: a GPC statement does not change after its import, so pairing it again
     * finds nothing new. Groups without an unpaid payment are left out, so old fully paid groups cost nothing.
     * Each bank account is paired on its own, so one unreachable bank or invalid token does not stop the others.
     *
     * @return PairingResult[]
     */
    public function pairAutomaticGroups(): array
    {
        $groups = $this->groups->findAutomaticPairingEnabled();
        $groupsWithOpenPayments = array_flip(array_map(
            static fn (Payment $payment): int => $payment->getGroupId(),
            $this->payments->findOpenByGroups(array_values(array_map(static fn (Group $group): int => (int) $group->getId(), $groups))),
        ));
        $groupIdsByBankAccount = [];

        foreach ($groups as $group) {
            $bankAccountId = $group->getBankAccountId();

            if ($bankAccountId !== null && isset($groupsWithOpenPayments[(int) $group->getId()])) {
                $groupIdsByBankAccount[$bankAccountId][] = (int) $group->getId();
            }
        }

        $groupIdsByBankAccount = array_filter(
            $groupIdsByBankAccount,
            fn (int $bankAccountId): bool => $this->isFioAccount($bankAccountId),
            ARRAY_FILTER_USE_KEY,
        );
        $pairingResults = [];

        foreach ($groupIdsByBankAccount as $bankAccountId => $groupIds) {
            try {
                $pairingResults = [...$pairingResults, ...$this->pairAllGroups($groupIds)];
            } catch (BankTimeLimit|BankTimeout|BankWrongTokenAccount $exception) {
                $this->logger->warning(
                    sprintf('Automatic pairing of bank account #%d failed: %s', $bankAccountId, $exception->getMessage()),
                    ['exception' => $exception],
                );
            }
        }

        $this->logger->info(sprintf(
            'Automatic pairing paired %d payments on %d bank accounts',
            array_sum(array_map(static fn (PairingResult $result): int => $result->getCount(), $pairingResults)),
            count($groupIdsByBankAccount),
        ));

        return $pairingResults;
    }

    /**
     * Pairs the open groups of a GPC account after a statement import. The window starts at the first day
     * of the statement instead of the last pairing, because a statement may bring older days.
     *
     * @return PairingResult[]
     */
    public function pairImportedStatement(int $bankAccountId, ChronosDate $statementStart): array
    {
        $groupIds = array_values(array_map(
            static fn (Group $group): int => (int) $group->getId(),
            array_filter(
                $this->groups->findByBankAccount($bankAccountId),
                static fn (Group $group): bool => $group->getState() === Group::STATE_OPEN,
            ),
        ));

        if ($groupIds === []) {
            return [];
        }

        return $this->pairAllGroups($groupIds, max(1, $statementStart->diffInDays(ChronosDate::today(), false)));
    }

    private function isFioAccount(int $bankAccountId): bool
    {
        try {
            return $this->bankAccounts->find($bankAccountId)->getTransactionSource() === BankTransactionSource::FIO;
        } catch (BankAccountNotFound) {
            return false;
        }
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
                array_map(
                    fn (Group $g) => $g->getLastPairing()
                        ?? ChronosDate::today()->subDays($g->getPairingDaysBack() ?? self::DAYS_BACK_DEFAULT)->toNative(),
                    $groups,
                ),
            ),
        );
    }
}
