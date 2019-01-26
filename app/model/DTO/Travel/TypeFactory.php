<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Model\DTO\Travel\TravelType as TypeDTO;
use Model\Travel\Travel\Type;
use Nette\StaticClass;

final class TypeFactory
{
    use StaticClass;

    public static function create(Type $type) : TypeDTO
    {
        return new TypeDTO($type->getShortcut(), $type->getLabel(), $type->hasFuel());
    }
}
