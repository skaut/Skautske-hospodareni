<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Bank\BankService;
use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\PairingResult;
use App\Model\Payment\PaymentService;

use function array_filter;
use function array_map;
use function count;

final class GroupPairButtonScope implements PairButtonScope
{
    /** @param int[] $groupIds */
    public function __construct(
        private readonly PaymentService $payments,
        private readonly BankService $bankService,
        private readonly PairButtonBankAccountSupport $bankAccountSupport,
        private readonly array $groupIds,
    ) {
    }

    public function getItemsCount(): int
    {
        return count($this->groupIds);
    }

    public function canPair(): bool
    {
        if ($this->groupIds === []) {
            return false;
        }

        $bankAccountIds = array_map(
            static fn (Group $group): ?int => $group->getBankAccountId(),
            $this->payments->findGroupsByIds($this->groupIds),
        );

        return $this->bankAccountSupport->hasPairableBankAccount(array_filter($bankAccountIds));
    }

    public function getDaysBackDefault(): int
    {
        return BankService::DAYS_BACK_DEFAULT;
    }

    public function getDisabledReason(): string
    {
        return 'Platební skupina nemá dostupný bankovní účet pro párování nebo u FIO účtu chybí API token.';
    }

    public function pair(?int $daysBack = null): array
    {
        $pairingResults = $this->bankService->pairAllGroups($this->groupIds, $daysBack);
        $messages = [];

        if ($pairingResults === []) {
            $messages[] = new PairButtonFlashMessage('Nebyly nalezeny žádné úhrady k párování.', 'warning');
        }

        foreach ($pairingResults as $pairingResult) {
            $messages[] = $this->createResultMessage($pairingResult);
        }

        return $messages;
    }

    private function createResultMessage(PairingResult $pairingResult): PairButtonFlashMessage
    {
        return new PairButtonFlashMessage($pairingResult->getMessage(), $pairingResult->getCount() > 0 ? 'success' : 'info');
    }
}
