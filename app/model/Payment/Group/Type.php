<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Consistence\Enum\Enum;

class Type extends Enum
{
    public const CAMP         = 'camp';
    public const REGISTRATION = 'registration';
    public const EVENT        = 'event';
    public const EDUCATION    = 'education';

    public function __toString() : string
    {
        return $this->getValue();
    }

    public static function CAMP() : self
    {
        return self::get(self::CAMP);
    }

    public static function REGISTRATION() : self
    {
        return self::get(self::REGISTRATION);
    }

    public static function EVENT() : self
    {
        return self::get(self::EVENT);
    }

    public static function EDUCATION() : self
    {
        return self::get(self::EDUCATION);
    }
}
