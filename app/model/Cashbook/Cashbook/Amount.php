<?php

namespace Model\Cashbook\Cashbook;

use Nette\SmartObject;

/**
 * @property-read string $expression
 * @property-read float $value
 */
class Amount
{

    use SmartObject;

    /** @var string */
    private $expression;

    /** @var float */
    private $value;

    public function __construct(string $expression)
    {
        $this->expression = str_replace(',', '.', $expression);
        $this->value = $this->calculateValue();

        if ($this->value <= 0) {
            throw new \InvalidArgumentException('Expression result must be larger than 0');
        }
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @deprecated use self::toFloat()
     */
    public function getValue(): float
    {
        return $this->value;
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    /**
     * Evaluates expression of numbers and + and * operators
     */
    private function calculateValue(): float
    {
        $expression = str_replace(' ', '', $this->expression);
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
