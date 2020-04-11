<?php

declare(strict_types=1);

namespace Model\Cashbook;

use App\AccountancyModule\AccountancyHelpers;
use Cake\Chronos\Date;
use Codeception\Test\Unit;
use InvalidArgumentException;
use Model\Utils\MoneyFactory;
use Money\Money;

class AccountancyHelpersTest extends Unit
{
    public function testDateRangeOnlyOneArgument() : void
    {
        $this->expectException(InvalidArgumentException::class);
        AccountancyHelpers::dateRange([new Date('2019-08-01')]);
    }

    public function testDateRangeToMuchArguments() : void
    {
        $this->expectException(InvalidArgumentException::class);
        AccountancyHelpers::dateRange([
            new Date('2019-08-01'),
            new Date('2019-08-02'),
            new Date('2019-08-03'),
        ]);
    }

    /**
     * @return mixed[]
     */
    public function getDateRanges() : array
    {
        return [
            [new Date('2019-08-01'), '1. 8. 2019'],
            [new Date('2019-08-02'), '1. - 2. 8. 2019'],
            [new Date('2019-09-01'), '1. 8. - 1. 9. 2019'],
            [new Date('2020-01-01'), '1. 8. 2019 - 1. 1. 2020'],
        ];
    }

    /**
     * @dataProvider getDateRanges
     */
    public function testDateRange(Date $end, string $expectedResult) : void
    {
        $res = AccountancyHelpers::dateRange([
            new Date('2019-08-01'),
            $end,
        ]);
        $this->assertSame($expectedResult, $res);
    }

    /**
     * @return mixed[]
     */
    public function getPrices() : array
    {
        return [
            [1.12, '1,12'],
            [2.451, '2,45'],
            [3.459, '3,46'],
            ['4', '4,00'],
            ['5a', '5,00'],
            ['a6', '0,00'],
            [MoneyFactory::fromFloat(7.65), '7,65'],
            [null, ' '],
        ];
    }

    /**
     * @param float|string|Money|null $price
     *
     * @dataProvider getPrices
     */
    public function testPrice($price, string $expected) : void
    {
        $this->assertSame($expected, AccountancyHelpers::price($price));
    }

    /**
     * @return mixed[]
     */
    public function getNumbers() : array
    {
        return [
            [1, '1'],
            ['2.2', '2,20'],
            ['33345', '33 345'],
            [4.5, '4,50'],
            ['5,5', '5'],
        ];
    }

    /**
     * @param int|float|string $number
     *
     * @dataProvider getNumbers
     */
    public function testNum($number, string $expected) : void
    {
        $this->assertSame($expected, AccountancyHelpers::num($number));
    }

    public function testPostCode() : void
    {
        $this->assertSame('110 00', AccountancyHelpers::postCode('A1 10FJV0 0'));
        $this->assertSame('moje psc', AccountancyHelpers::postCode('moje psc'));
    }

    public function testPriceToString() : void
    {
        $this->assertSame('Jedna', AccountancyHelpers::priceToString(1.0));
        $this->assertSame('Dvacetsedm', AccountancyHelpers::priceToString(27.0));
        $this->assertSame('Třicetpět', AccountancyHelpers::priceToString(35.2));
        $this->assertSame('Jednostotisícdevětsetpadesátosm', AccountancyHelpers::priceToString(100958.0));
        $this->assertSame('PŘÍLIŠ VYSOKÉ ČÍSLO', AccountancyHelpers::priceToString(8777666.0));
    }
}
