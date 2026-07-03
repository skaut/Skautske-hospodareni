<?php

declare(strict_types=1);

namespace App\Model\Common\Repositories;

use App\Model\Common\User;
use App\Model\Common\UserNotFound;

interface IUserRepository
{
    /** @throws UserNotFound */
    public function find(int $id): User;

    /** @throws UserNotFound */
    public function getCurrentUser(): User;
}
