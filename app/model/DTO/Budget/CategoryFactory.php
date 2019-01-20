<?php

declare(strict_types=1);

namespace Model\DTO\Budget;

use Nette\StaticClass;
use function array_map;

final class CategoryFactory
{
    use StaticClass;

    public static function create(\Model\Budget\Unit\Category $category) : Category
    {
        return new Category(
            $category->getId(),
            $category->getLabel(),
            $category->getValue(),
            array_map([self::class, 'create'], $category->getChildren())
        );
    }
}
