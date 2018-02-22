<?php

namespace Model\Cashbook;

use Consistence\Enum\Enum;

class Operation extends Enum
{

    public const INCOME = 'in';
    public const EXPENSE = 'out';

}
