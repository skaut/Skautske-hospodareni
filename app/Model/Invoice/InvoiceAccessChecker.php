<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use App\Model\User\Repository\InvoiceAccessUserRepository;
use Nette\Security\IIdentity;
use Nette\Security\User;

use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_numeric;
use function sort;

final class InvoiceAccessChecker
{
    /** @param mixed[] $invoiceAccessAllowedUserIds */
    public function __construct(
        private User $user,
        private InvoiceAccessUserRepository $accessUserRepository,
        private array $invoiceAccessAllowedUserIds,
    ) {
    }

    /** @return int[] */
    public function getConfiguredAllowedUserIds(): array
    {
        $userIds = array_map('intval', $this->invoiceAccessAllowedUserIds);
        $userIds = array_values(array_unique($userIds));
        sort($userIds);

        return $userIds;
    }

    public function isCurrentUserAllowed(): bool
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return false;
        }

        if ($this->accessUserRepository->hasUserId($userId)) {
            return true;
        }

        return in_array($userId, $this->getConfiguredAllowedUserIds(), true);
    }

    public function getCurrentUserId(): ?int
    {
        $identity = $this->user->getIdentity();

        if (! $identity instanceof IIdentity || ! is_numeric($identity->getId())) {
            return null;
        }

        return (int) $identity->getId();
    }
}
