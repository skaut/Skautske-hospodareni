<?php

declare(strict_types=1);

namespace Model\Services;

use Nette\StaticClass;

final class Calculator
{

    use StaticClass;

    /**
     * Evaluates expression of numbers and + and * operators
     */
    public static function calculate(string $expression): float
    {
        $expression = str_replace([" ", ","], ["", "."], $expression);
        preg_match_all('/(?P<number>-?[0-9]+([.][0-9]{1,})?)(?P<operator>[\+\*]+)?/', $expression, $matches);
        $maxIndex = count($matches['number']);
        foreach ($matches['operator'] as $index => $op) { //vyřeší operaci násobení
            if ($op === '*' && $index < $maxIndex) {
                $matches['number'][$index + 1] = $matches['number'][$index] * $matches['number'][$index + 1];
                $matches['number'][$index] = 0;
            }
        }
        return array_sum($matches['number']);
    }

}
