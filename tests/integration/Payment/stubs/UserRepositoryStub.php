<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Common\User;

class UserRepositoryStub implements IUserRepository
{
    private User $user;

    public function find(int $id): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getCurrentUser(): User
    {
        return $this->user;
    }
}
