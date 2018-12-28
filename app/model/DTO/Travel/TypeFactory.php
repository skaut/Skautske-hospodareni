<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Model\DTO\Travel\Type as TypeDTO;
use Model\Travel\Travel\Type;
use Nette\StaticClass;

final class TypeFactory
{
    use StaticClass;

    public static function create(Type $type) : TypeDTO
    {
        return new TypeDTO($type->getType(), $type->getLabel(), $type->hasFuel());
    }
}
