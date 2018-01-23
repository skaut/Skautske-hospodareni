<?php

namespace Model\Common\Repositories;

use Model\Common\User;
use Model\Common\UserNotFoundException;

interface IUserRepository
{

    /**
     * @throws UserNotFoundException
     */
    public function find(int $id): User;

    /**
     * @throws UserNotFoundException
     */
    public function getCurrentUser(): User;

}
