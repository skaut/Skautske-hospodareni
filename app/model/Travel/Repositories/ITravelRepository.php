<?php

declare(strict_types=1);

namespace Model\Travel\Repositories;

use Model\Travel\Travel\Type;

interface ITravelRepository
{
    public function getType(string $type) : Type;
}
