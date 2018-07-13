<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Enum\Enum;

/**
 * @TODO move to Common BC
 */
class Operation extends Enum
{
    public const INCOME  = 'in';
    public const EXPENSE = 'out';

    public function getInverseOperation() : self
    {
        return self::get(
            $this->getValue() === self::INCOME ? self::EXPENSE : self::INCOME
        );
    }
}
