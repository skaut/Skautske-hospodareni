<?php

namespace Model\Payment\Repositories;

use Model\Payment\User;
use Model\Payment\UserNotFoundException;

interface IUserRepository
{

    /**
     * @throws UserNotFoundException
     */
    public function find(int $id) : User;

}
