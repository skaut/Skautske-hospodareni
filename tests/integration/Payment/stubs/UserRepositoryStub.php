<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Common\Repositories\IUserRepository;
use Model\Common\User;

class UserRepositoryStub implements IUserRepository
{
    /** @var User */
    private $user;

    public function find(int $id) : User
    {
        return $this->user;
    }

    public function setUser(User $user) : void
    {
        $this->user = $user;
    }

    public function getCurrentUser() : User
    {
        return $this->user;
    }
}
