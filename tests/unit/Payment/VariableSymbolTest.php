<?php

declare(strict_types=1);

namespace Model\Payment;

use Codeception\Test\Unit;

class VariableSymbolTest extends Unit
{
    public function testToStringReturnsValue() : void
    {
        $this->assertSame('123', (string) new VariableSymbol('123'));
    }

    public function testVariableSymbolCantBeLongerThanTenSymbols() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol('12345678910');
    }

    public function testVariableSymbolCantBeEmpty() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol('');
    }

    /**
     * @dataProvider getVariableSymbolsStartingWithZero
     */
    public function testVariableSymbolCantStartWithZero(string $symbol) : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol($symbol);
    }


    /**
     * @dataProvider getNonNumericSymbol
     */
    public function testVariableSymbolCantContainNonNumericSymbols(string $data) : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariableSymbol('123' . $data[0]);
    }

    /**
     * @return string[][]
     */
    public function getNonNumericSymbol() : array
    {
        return [
            ['a'],
            ['#'],
            [' '],
            ['-'],
        ];
    }

    /**
     * @return string[][]
     */
    public function getVariableSymbolsStartingWithZero() : array
    {
        return [
            ['0123'],
            ['00123'],
        ];
    }

    public function testAreEqual() : void
    {
        $first  = new VariableSymbol('123');
        $second = new VariableSymbol('123');

        $this->assertTrue(
            VariableSymbol::areEqual($second, $first),
            'Variable symbol doesn\'t match other instance with same value'
        );
    }

    public function areNotEqual(?VariableSymbol $first, ?VariableSymbol $second) : void
    {
        $this->assertFalse(VariableSymbol::areEqual($first, $second));
    }

    /**
     * @return mixed[]
     */
    public function getNotEqualPairs() : array
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('456')],
            [new VariableSymbol('123'), null],
            [null, new VariableSymbol('123')],
        ];
    }

    public function testIntValue() : void
    {
        $variableSymbol = new VariableSymbol('123');

        $this->assertSame(123, $variableSymbol->toInt());
    }
}
