<?php

declare(strict_types=1);

namespace Model\Services;

use Model\Cashbook\Cashbook\Amount;
use Nette\StaticClass;

final class Calculator
{

    use StaticClass;

    /**
     * Evaluates expression of numbers and + and * operators
     */
    public static function calculate(string $expression): float
    {
        return (new Amount($expression))->getValue();
    }

}
