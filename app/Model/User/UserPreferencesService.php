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

    public function setShowHelp(bool $showHelp): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        $this->manager->saveHelpVisibility($userId, $showHelp);
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
