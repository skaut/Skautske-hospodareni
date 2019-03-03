<?php

declare(strict_types=1);

namespace Model\Travel\Repositories;

use Model\Travel\Travel\Type;
use Model\Travel\TypeNotFound;

interface ITypeRepository
{
    /**
     * @throws TypeNotFound
     */
    public function find(string $shortcut) : Type;
}
