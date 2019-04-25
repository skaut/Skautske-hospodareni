<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Nette\SmartObject;
use function array_sum;
use function count;
use function preg_match;
use function preg_match_all;
use function str_replace;

/**
 * @ORM\Embeddable()
 *
 * @property-read string $expression
 * @property-read float $value
 */
class Amount
{
    use SmartObject;

    /**
     * @ORM\Column(type="string", name="priceText", length=100)
     *
     * @var string
     */
    private $expression;

    /**
     * @ORM\Column(type="float", name="price", options={"unsigned"=true})
     *
     * @var float
     */
    private $value;

    public function __construct(string $expression)
    {
        $this->expression = str_replace(',', '.', $expression);
        $this->value      = $this->calculateValue();

        if ($this->value <= 0) {
            throw new InvalidArgumentException('Expression result must be larger than 0');
        }
    }

    public function getExpression() : string
    {
        return $this->expression;
    }

    /**
     * @deprecated use self::toFloat()
     */
    public function getValue() : float
    {
        return $this->value;
    }

    public function toFloat() : float
    {
        return $this->value;
    }

    public static function fromFloat(float $amount) : self
    {
        return new self((string) $amount);
    }

    public function isUsingFormula() : bool
    {
        return preg_match('/[+*]/', $this->expression) === 1;
    }

    /**
     * Evaluates expression of numbers and + and * operators
     */
    private function calculateValue() : float
    {
        $expression = str_replace(' ', '', $this->expression);
        preg_match_all('/(?P<number>-?[0-9]+([.][0-9]{1,})?)(?P<operator>[\+\*]+)?/', $expression, $matches);
        $maxIndex = count($matches['number']);
        foreach ($matches['operator'] as $index => $op) { //vyřeší operaci násobení
            if ($op !== '*' || $index >= $maxIndex) {
                continue;
            }

            $matches['number'][$index + 1] = $matches['number'][$index] * $matches['number'][$index + 1];
            $matches['number'][$index]     = 0;
        }

        return array_sum($matches['number']);
    }
}
