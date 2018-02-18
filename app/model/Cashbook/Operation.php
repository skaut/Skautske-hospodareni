<?php

namespace Model\Cashbook;

use Consistence\Enum\Enum;

class Operation extends Enum
{

    public const INCOME = 'in';
    public const EXPENSE = 'out';

    public function compareWith(Operation $other): int
    {
        return $this->getValue() <=> $other->getValue();
    }

}
