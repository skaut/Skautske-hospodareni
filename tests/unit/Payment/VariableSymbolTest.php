<?php

namespace Model\Payment;

class VariableSymbolTest extends \Codeception\Test\Unit
{

    public function testToStringReturnsValue()
    {
        $this->assertSame('123', (string) new VariableSymbol('123'));
    }

    public function testVariableSymbolCantBeLongerThanTenSymbols()
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol('12345678910');
    }

    public function testVariableSymbolCantBeEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol('');
    }


    /**
     * @dataProvider getNonNumericSymbol
     */
    public function testVariableSymbolCantContainNonNumericSymbols($data)
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol('123' . $data[0]);
    }

    public function getNonNumericSymbol()
    {
        return [
            ['a'],
            ['#'],
            [' '],
            ['-'],
        ];
    }

    public function testVariableSymbolWithLeadingZerosEqualsOneWithout()
    {
        $symbol = new VariableSymbol('000123');
        $withoutZeros = new VariableSymbol('123');

        $this->assertTrue(
            $symbol->equals($withoutZeros),
            'Variable symbol doesn\'t match variable symbol with same value without leading zeroes');
    }

    public function testVariableSymbolWithoutLeadingZerosEqualsOneWith()
    {
        $symbol = new VariableSymbol('123');
        $withZeros = new VariableSymbol('00123');

        $this->assertTrue(
            $symbol->equals($withZeros),
            'Variable symbol doesn\'t match variable symbol with same value with leading zeroes');
    }

    public function testIncrementWithLeadingZeros()
    {
        $symbol = new VariableSymbol('000123');

        $this->assertSame('000124', (string) $symbol->increment());
    }

    public function testIncrementWithLeadingZerosOverBase()
    {
        $symbol = new VariableSymbol('000999');

        $this->assertSame('001000', (string) $symbol->increment());
    }

}
