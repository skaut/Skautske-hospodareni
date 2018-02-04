<?php

namespace Model\Cashbook\Category;

use Model\Cashbook\Category;
use Model\Cashbook\ObjectType as ObjectTypeEnum;

class ObjectType
{

    /** @var Category */
    private $category;

    /** @var ObjectTypeEnum */
    private $type;

    public function __construct(Category $category, ObjectTypeEnum $value)
    {
        $this->category = $category;
        $this->type = $value;
    }

    public function getType(): ObjectTypeEnum
    {
        return $this->type;
    }

}
