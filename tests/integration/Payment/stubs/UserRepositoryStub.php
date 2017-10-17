<?php

namespace Model\Payment;

use Model\Payment\Repositories\IUserRepository;

class UserRepositoryStub implements IUserRepository
{

    /** @var User[] */
    private $users = [];

    public function find(int $id): User
    {
        return $this->users[$id];
    }

    public function setUser(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

}
