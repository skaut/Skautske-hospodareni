<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\User\Manager\UserPreferenceManager;
use App\Model\User\Repository\UserPreferenceRepository;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Throwable;

use function in_array;
use function is_numeric;

final class UserPreferencesService
{
    public function __construct(
        private User $user,
        private UserPreferenceRepository $repository,
        private UserPreferenceManager $manager,
    ) {
    }

    public function shouldShowHelp(): bool
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return true;
        }

        try {
            return $this->repository->findOneByUserId($userId)?->shouldShowHelp() ?? true;
        } catch (Throwable) {
            return true;
        }
    }

    public function shouldExtendSkautisLogin(): bool
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return false;
        }

        try {
            return $this->repository->findOneByUserId($userId)?->shouldExtendSkautisLogin() ?? false;
        } catch (Throwable) {
            return false;
        }
    }

    public function shouldRememberSkautisRole(): bool
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return false;
        }

        try {
            return $this->repository->findOneByUserId($userId)?->shouldRememberSkautisRole() ?? false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param int[] $availableRoleIds
     */
    public function getRememberedSkautisRoleIdForLogin(int $userId, array $availableRoleIds): ?int
    {
        try {
            $preference = $this->repository->findOneByUserId($userId);
            if ($preference === null || ! $preference->shouldRememberSkautisRole()) {
                return null;
            }

            $rememberedRoleId = $preference->getRememberedSkautisRoleId();
            if ($rememberedRoleId === null) {
                return null;
            }

            if (in_array($rememberedRoleId, $availableRoleIds, true)) {
                return $rememberedRoleId;
            }

            $this->manager->clearRememberedSkautisRole($userId);

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function rememberCurrentSkautisRole(int $roleId): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        try {
            $preference = $this->repository->findOneByUserId($userId);
            if ($preference === null || ! $preference->shouldRememberSkautisRole()) {
                return;
            }

            $this->manager->saveRememberedSkautisRole($userId, $roleId);
        } catch (Throwable) {
        }
    }

    public function setShowHelp(bool $showHelp): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        $this->manager->saveHelpVisibility($userId, $showHelp);
    }

    public function setPreferences(
        bool $showHelp,
        bool $extendSkautisLogin,
        bool $rememberSkautisRole,
        ?int $currentSkautisRoleId = null,
    ): void {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        $this->manager->savePreferences($userId, $showHelp, $extendSkautisLogin, $rememberSkautisRole);
        if ($rememberSkautisRole && $currentSkautisRoleId !== null) {
            $this->manager->saveRememberedSkautisRole($userId, $currentSkautisRoleId);
        }
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
