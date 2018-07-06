<?php

declare(strict_types=1);

namespace Model\Utils;

use Codeception\Test\Unit;
use Money\Currency;
use Money\Money;

class MoneyFactoryTest extends Unit
{
    public function testFromFloatCreatesInstanceWithCorrectAmountAndCurrency() : void
    {
        $money = MoneyFactory::fromFloat(69.99);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame('6999', $money->getAmount());
        $this->assertSame('CZK', $money->getCurrency()->getCode());
    }

    public function testToFloatReturnsCorrectValue() : void
    {
        $money = new Money('4599', new Currency('CZK'));

        $this->assertSame(45.99, MoneyFactory::toFloat($money));
    }

    public function testZeroReturnsInstanceWithZeroValueAndCorrectCurrency() : void
    {
        $money = MoneyFactory::zero();

        $this->assertSame('0', $money->getAmount());
        $this->assertSame('CZK', $money->getCurrency()->getCode());
    }

    /**
     * @dataProvider dataFloor
     */
    public function testFloor(float $amount, float $flooredAmount) : void
    {
        $money = MoneyFactory::fromFloat($amount);

        $this->assertTrue(
            MoneyFactory::floor($money)->equals(MoneyFactory::fromFloat($flooredAmount))
        );
    }

    public function dataFloor() : array
    {
        return [
            [15.1, 15],
            [15.5, 15],
            [15.8, 15],
        ];
    }
}
