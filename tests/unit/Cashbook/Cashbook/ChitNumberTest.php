<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Codeception\Test\Unit;

class ChitNumberTest extends Unit
{

    /**
     * @dataProvider getInvalidNumbers
     */
    public function testInvalidChitNumbersThrowException(string $value, string $reason): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ChitNumber($value);

        $this->fail("Exception for '$reason' not thrown'");
    }

    /**
     * @dataProvider getValidNumbers
     */
    public function testValidNumbers(string $value): void
    {
        $number = new ChitNumber($value);

        $this->assertSame($value, $number->getValue());
    }

    public function getInvalidNumbers(): array
    {
        return [
            ['123456', 'longer than 6 symbols'],
            ['A', 'letters only'],
            ['1A', 'letter postfix'],
            ['', 'empty number'],
            ['$1', 'non-alphanumeric symbol'],
            ['ABCD1', 'prefix longer than 3 symbols']
        ];
    }

    public function getValidNumbers(): array
    {
        return [
            ['12345'],
            ['1'],
            ['ABC1'],
            ['A1'],
            ['0'],
            ['A0'],
        ];
    }

}
