<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Common\User;
use App\Model\Common\UserNotFound;
use Throwable;

class UserRepositoryStub implements IUserRepository
{
    private ?User $user = null;

    private ?Throwable $failure = null;

    /** Simulates skautIS failing to tell who is logged in; null stops it. */
    public function failWith(?Throwable $failure): void
    {
        $this->failure = $failure;
    }

    public function find(int $id): User
    {
        return $this->getCurrentUser();
    }

    /** Null simulates a run without a logged-in user, like cron. */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getCurrentUser(): User
    {
        return $this->user ?? throw new UserNotFound();
    }

    public function findCurrentUser(): ?User
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->user;
    }
}
