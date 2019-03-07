<?php

declare(strict_types=1);

namespace Model\Payment\BankAccount;

use Codeception\Test\Unit;

class AccountNumberTest extends Unit
{
    public function testToString() : void
    {
        $number = new AccountNumber(null, '2000942144', '2010');

        $this->assertSame('2000942144/2010', (string) $number);
    }

    public function testToStringWithPrefix() : void
    {
        $number = new AccountNumber('19', '17608231', '0100'); // To je E-ON btw

        $this->assertSame('19-17608231/0100', (string) $number);
    }

    /**
     * @dataProvider dataGetAccountNumberWithPrefix
     */
    public function testGetAccountNumberWithPrefix(string $fullAccountNumber, string $expectedNumberWithPrefix) : void
    {
        $this->assertSame(
            $expectedNumberWithPrefix,
            AccountNumber::fromString($fullAccountNumber)->getNumberWithPrefix()
        );
    }

    /**
     * @return string[][]
     */
    public function dataGetAccountNumberWithPrefix() : array
    {
        return [
            ['2000942144/2010', '2000942144'],
            ['19-17608231/0100', '19-17608231'],
        ];
    }

    public function testIsValidForValidNumber() : void
    {
        $this->assertTrue(AccountNumber::isValid('19-17608231/0100'));
    }

    public function testIsValidForInvalidNumber() : void
    {
        $this->assertFalse(AccountNumber::isValid('123'));
    }
    public function testFromString() : void
    {
        $number = AccountNumber::fromString('2000942144/2010');

        $this->assertNull($number->getPrefix());
        $this->assertSame('2000942144', $number->getNumber());
        $this->assertSame('2010', $number->getBankCode());
    }
}
