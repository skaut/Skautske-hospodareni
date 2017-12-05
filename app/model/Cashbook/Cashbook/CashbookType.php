<?php

namespace Model\Cashbook\Cashbook;

use Consistence\Enum\Enum;

class CashbookType extends Enum
{

    public const EVENT = 'general';
    public const OFFICIAL_UNIT = 'official_unit';
    public const TROOP = 'troop';
    public const CAMP = 'camp';

}
