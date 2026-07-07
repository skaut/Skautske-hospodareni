<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\User\Manager\UserPreferenceManager;
use App\Model\User\Repository\UserPreferenceRepository;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Throwable;

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

    public function setShowHelp(bool $showHelp): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        $this->manager->saveHelpVisibility($userId, $showHelp);
    }

    public function setPreferences(bool $showHelp, bool $extendSkautisLogin): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        $this->manager->savePreferences($userId, $showHelp, $extendSkautisLogin);
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
