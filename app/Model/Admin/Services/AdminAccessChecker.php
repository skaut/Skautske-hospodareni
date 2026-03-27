<?php

declare(strict_types=1);

namespace App\Model\Admin\Services;

use App\Model\User\Repository\AdminUserRepository;
use Nette\Security\IIdentity;
use Nette\Security\User;

use function array_map;
use function array_unique;
use function in_array;
use function is_numeric;
use function sort;

final class AdminAccessChecker
{
    /** @param mixed[] $adminAllowedUserIds */
    public function __construct(
        private User $user,
        private AdminUserRepository $adminUserRepository,
        private array $adminAllowedUserIds,
    ) {
    }

    /** @return int[] */
    public function getConfiguredAdminUserIds(): array
    {
        $userIds = array_map('intval', $this->adminAllowedUserIds);
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

        if ($this->adminUserRepository->hasUserId($userId)) {
            return true;
        }

        return in_array($userId, $this->getConfiguredAdminUserIds(), true);
    }

    private function getCurrentUserId(): ?int
    {
        $identity = $this->user->getIdentity();

        if (! $identity instanceof IIdentity || ! is_numeric($identity->getId())) {
            return null;
        }

        return (int) $identity->getId();
    }
}
