<?php

declare(strict_types=1);

namespace Model\Cashbook;

use App\AccountancyModule\AccountancyHelpers;
use Codeception\Test\Unit;
use InvalidArgumentException;

class AccountancyHelpersTest extends Unit
{
    public function testDateRangeOnlyOneArgument() : void
    {
        $this->expectException(InvalidArgumentException::class);
        AccountancyHelpers::dateRange(['2019-08-01']);
    }

    public function testDateRangeToMuchArguments() : void
    {
        $this->expectException(InvalidArgumentException::class);
        AccountancyHelpers::dateRange([
            '2019-08-01',
            '2019-08-02',
            '2019-08-03',
        ]);
    }

    /**
     * @return mixed[]
     */
    public function getDateRanges() : array
    {
        return [
            ['2019-08-01', '1. 8. 2019'],
            ['2019-08-02', '1. - 2. 8. 2019'],
            ['2019-09-01', '1. 8. - 1. 9. 2019'],
            ['2020-01-01', '1. 8. 2019 - 1. 1. 2020'],
        ];
    }

    /**
     * @dataProvider getDateRanges
     */
    public function testDateRange(string $end, string $expectedResult) : void
    {
        $res = AccountancyHelpers::dateRange([
            '2019-08-01',
            $end,
        ]);
        $this->assertSame($expectedResult, $res);
    }
}
