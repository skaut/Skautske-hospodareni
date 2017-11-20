<?php

namespace Model\Utils;

use Money\Currency;
use Money\Money;

class MoneyFactoryTest extends \Codeception\Test\Unit
{

    public function testFromFloatCreatesInstanceWithCorrectAmountAndCurrency()
    {
        $money = MoneyFactory::fromFloat(69.99);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame('6999', $money->getAmount());
        $this->assertSame('CZK', $money->getCurrency()->getCode());
    }

    public function testToFloatReturnsCorrectValue()
    {
        $money = new Money('4599', new Currency('CZK'));

        $this->assertSame(45.99, MoneyFactory::toFloat($money));
    }

    public function testZeroReturnsInstanceWithZeroValueAndCorrectCurrency()
    {
        $money = MoneyFactory::zero();

        $this->assertSame('0', $money->getAmount());
        $this->assertSame('CZK', $money->getCurrency()->getCode());
    }

}
