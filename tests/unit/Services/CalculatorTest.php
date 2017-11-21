<?php

namespace Model\Services;

class CalculatorTest extends \Codeception\Test\Unit
{

    /**
     * @dataProvider getSums
     */
    public function testCalculateSum(string $expression, float $expectedResult): void
    {
        $this->assertSame($expectedResult, Calculator::calculate($expression));
    }

    /**
     * @return array[]
     */
    public function getSums(): array
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
    public function testCalculateMultiplication(string $expression, float $expectedResult): void
    {
        $this->assertSame($expectedResult, Calculator::calculate($expression));
    }

    /**
     * @return array[]
     */
    public function getMultiplications(): array
    {
        return [
            ['5 * 5', 25.0],
            ['5*5', 25.0],
            ['5*0', 0.0],
            ['3*3*3', 27.0],
        ];
    }

    /**
     * @dataProvider getMultiplications
     */
    public function testCalculateSumsAndMultiplications(string $expression, float $expectedResult): void
    {
        $this->assertSame($expectedResult, Calculator::calculate($expression));
    }

    /**
     * @return array[]
     */
    public function getMultiplicationsWithSums(): array
    {
        return [
            ['5*5+5', 30.0],
            ['5+5*5', 30.0],
            ['5*5+5*5', 30.0],
        ];
    }

}
