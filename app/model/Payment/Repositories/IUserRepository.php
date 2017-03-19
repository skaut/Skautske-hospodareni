<?php

namespace Model\Payment\Repositories;

use Model\Payment\User;
use Model\Payment\UserNotFoundException;

interface IUserRepository
{

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function find(int $id) : User;

}
