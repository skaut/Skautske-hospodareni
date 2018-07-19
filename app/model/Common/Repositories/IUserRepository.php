<?php

declare(strict_types=1);

namespace Model\Common\Repositories;

use Model\Common\User;
use Model\Common\UserNotFound;

interface IUserRepository
{
    /**
     * @throws UserNotFound
     */
    public function find(int $id) : User;

    /**
     * @throws UserNotFound
     */
    public function getCurrentUser() : User;
}
