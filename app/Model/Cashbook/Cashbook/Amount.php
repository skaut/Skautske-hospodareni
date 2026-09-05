<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Cashbook;

use App\Model\Utils\MoneyFactory;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Money\Money;
use Nette\SmartObject;

use function count;
use function intdiv;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_replace;

/**
 * @ORM\Embeddable()
 *
 * @property string $expression
 * @property Money  $value
 */
class Amount
{
    use SmartObject;

    /** @ORM\Column(type="string", name="priceText", length=100) */
    private string $expression;

    /** @ORM\Column(type="money", name="price") */
    private Money $value;

    public function __construct(string $expression)
    {
        $this->expression = str_replace(',', '.', $expression);
        $this->value = $this->calculateValue();

        if ($this->value->isZero() || $this->value->isNegative()) {
            throw new InvalidArgumentException(sprintf('Expression "%s" result must be larger than 0', $expression));
        }
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getValue(): Money
    {
        return $this->value;
    }

    public function toMoney(): Money
    {
        return $this->value;
    }

    /** @deprecated Convert to a decimal string or use toMoney() in domain code. */
    public function toFloat(): float
    {
        return (float) MoneyFactory::toDecimal($this->value);
    }

    public static function fromMoney(Money $amount): self
    {
        return new self(MoneyFactory::toDecimal($amount));
    }

    /** @deprecated Convert input at the application boundary and use fromMoney(). */
    public static function fromFloat(float $amount): self
    {
        return new self(sprintf('%.2F', $amount));
    }

    public function isUsingFormula(): bool
    {
        return preg_match('/[+*]/', $this->expression) === 1;
    }

    /**
     * Evaluates expression of numbers and + and * operators.
     */
    private function calculateValue(): Money
    {
        $expression = str_replace(' ', '', $this->expression);
        preg_match_all('/(?P<number>-?[0-9]+([.][0-9]{1,})?)(?P<operator>[\+\*]+)?/', $expression, $matches);
        $maxIndex = count($matches['number']);
        foreach ($matches['operator'] as $index => $op) { // vyřeší operaci násobení
            if ($op !== '*' || $index >= $maxIndex) {
                continue;
            }

            $left = MoneyFactory::fromDecimal($matches['number'][$index]);
            $right = MoneyFactory::fromDecimal($matches['number'][$index + 1]);
            $product = (int) $left->getAmount() * (int) $right->getAmount();
            if ($product % 100 !== 0) {
                throw new InvalidArgumentException(sprintf('Expression "%s" result cannot be represented in whole cents', $this->expression));
            }

            $matches['number'][$index + 1] = MoneyFactory::toDecimal(Money::CZK(intdiv($product, 100)));
            $matches['number'][$index] = '0';
        }

        $result = MoneyFactory::zero();
        foreach ($matches['number'] as $number) {
            $result = $result->add(MoneyFactory::fromDecimal($number));
        }

        return $result;
    }
}
