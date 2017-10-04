<?php

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;

interface ICategoryRepository
{

    /**
     * @return Category[]
     */
    public function findByObjectType(ObjectType $type): array;

}
