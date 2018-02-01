<?php

namespace Model\Cashbook\Cashbook;

use Consistence\Enum\Enum;
use Model\Cashbook\ObjectType;

class CashbookType extends Enum
{

    public const EVENT = 'general';
    public const OFFICIAL_UNIT = 'official_unit';
    public const TROOP = 'troop';
    public const CAMP = 'camp';

    public function getSkautisObjectType(): ObjectType
    {
        if(in_array($this->getValue(), [self::OFFICIAL_UNIT, self::TROOP], TRUE)) {
            return ObjectType::get(ObjectType::UNIT);
        }

        return ObjectType::get($this->getValue());
    }
}
