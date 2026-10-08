<?php

declare(strict_types=1);

namespace App\Model\Utils;

use Codeception\Test\Unit;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;

class MoneyFactoryTest extends Unit
{
    public function testFromFloatCreatesInstanceWithCorrectAmountAndCurrency(): void
    {
        $money = MoneyFactory::fromFloat(69.99);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame('6999', $money->getAmount());
        $this->assertSame('CZK', $money->getCurrency()->getCode());
    }

    public function testToFloatReturnsCorrectValue(): void
    {
        $money = new Money('4599', new Currency('CZK'));

        $this->assertSame(45.99, MoneyFactory::toFloat($money));
    }

    public function testZeroReturnsInstanceWithZeroValueAndCorrectCurrency(): void
    {
        $money = MoneyFactory::zero();

        $this->assertSame('0', $money->getAmount());
        $this->assertSame('CZK', $money->getCurrency()->getCode());
    }

    public function testFromDecimalKeepsCentsExact(): void
    {
        $money = MoneyFactory::fromDecimal('0,19');

        $this->assertSame('19', $money->getAmount());
        $this->assertSame('0.19', MoneyFactory::toDecimal($money));
    }

    public function testFromDecimalRejectsFractionsOfCents(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MoneyFactory::fromDecimal('0.001');
    }

    public function testMoneyArithmeticKeepsCentsExact(): void
    {
        $income = MoneyFactory::fromDecimal('0.10')->add(MoneyFactory::fromDecimal('0.20'));
        $balance = $income->subtract(MoneyFactory::fromDecimal('0.49'));

        $this->assertSame('30', $income->getAmount());
        $this->assertSame('-19', $balance->getAmount());
        $this->assertSame('-0.19', MoneyFactory::toDecimal($balance));
    }

    public function testDecimalFormattingPreservesNegativeCents(): void
    {
        $this->assertSame('-0.01', MoneyFactory::toDecimal(Money::CZK(-1)));
        $this->assertSame('-15.80', MoneyFactory::toDecimal(Money::CZK(-1580)));
    }

    /** @dataProvider dataFloor */
    public function testFloor(float $amount, float $flooredAmount): void
    {
        $money = MoneyFactory::fromFloat($amount);

        $this->assertTrue(
            MoneyFactory::floor($money)->equals(MoneyFactory::fromFloat($flooredAmount)),
        );
    }

    /** @return mixed[] */
    public function dataFloor(): array
    {
        return [
            [15.1, 15],
            [15.5, 15],
            [15.8, 15],
        ];
    }
}
