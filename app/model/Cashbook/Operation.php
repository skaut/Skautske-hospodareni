<?php

namespace Model\Cashbook;

use Consistence\Enum\Enum;

class Operation extends Enum
{

    public const INCOME = 'in';
    public const EXPENSE = 'out';

    public function getInverseOperation(): self
    {
        return self::get(
            $this->getValue() === self::INCOME ? self::EXPENSE : self::INCOME
        );
    }

}
