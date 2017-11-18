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
     * @dataProvider getVariableSymbolsStartingWithZero
     */
    public function testVariableSymbolCantStartWithZero(string $symbol)
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol($symbol);
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

    public function getVariableSymbolsStartingWithZero(): array
    {
        return [
            ['0123'],
            ['00123'],
        ];
    }

    public function testEquals()
    {
        $symbol = new VariableSymbol('123');
        $withoutZeros = new VariableSymbol('123');

        $this->assertTrue(
            $symbol->equals($withoutZeros),
            'Variable symbol doesn\'t match other instance with same value'
        );
    }

    public function testIntValue()
    {
        $variableSymbol = new VariableSymbol('123');

        $this->assertSame(123, $variableSymbol->toInt());
    }

}
