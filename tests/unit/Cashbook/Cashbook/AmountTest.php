<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Codeception\Test\Unit;

class AmountTest extends Unit
{
    /**
     * @dataProvider getSums
     */
    public function testCalculateSum(string $expression, float $expectedResult) : void
    {
        $amount = new Amount($expression);
        $this->assertSame($expectedResult, $amount->getValue());
    }

    public function testResultCantBeNegative() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Amount('-100');
    }

    public function testResultCantBeZero() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Amount('0');
    }

    /**
     * @return mixed[]
     */
    public function getSums() : array
    {
        return [
            ['5 + 5', 10.0],
            ['5+5', 10.0],
            ['5 + 0', 5.0],
            ['1 + 2 + 3', 6.0],
            ['1+2+3', 6.0],
        ];
    }

    /**
     * @dataProvider getMultiplications
     */
    public function testCalculateMultiplication(string $expression, float $expectedResult) : void
    {
        $amount = new Amount($expression);
        $this->assertSame($expectedResult, $amount->toFloat());
    }

    /**
     * @return mixed[]
     */
    public function getMultiplications() : array
    {
        return [
            ['5 * 5', 25.0],
            ['5*5', 25.0],
            ['3*3*3', 27.0],
        ];
    }

    /**
     * @dataProvider getMultiplications
     */
    public function testCalculateSumsAndMultiplications(string $expression, float $expectedResult) : void
    {
        $amount = new Amount($expression);
        $this->assertSame($expectedResult, $amount->getValue());
    }

    /**
     * @return mixed[]
     */
    public function getMultiplicationsWithSums() : array
    {
        return [
            ['5*5+5', 30.0],
            ['5+5*5', 30.0],
            ['5*5+5*5', 30.0],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getExpressions() : array
    {
        return [
            ['5 * 5', true],
            ['5+5', true],
            ['55', false],
        ];
    }

    /**
     * @dataProvider getMultiplications
     */
    public function testIsUsingFormula(string $expression, bool $expectedResult) : void
    {
        $amount = new Amount($expression);
        $this->assertSame($expectedResult, $amount->isUsingFormula());
    }
}
