<?php

namespace Model\Payment\BankAccount;

class AccountNumberTest extends \Codeception\Test\Unit
{

    public function testToString()
    {
        $number = new AccountNumber(NULL, '2000942144', '2010');

        $this->assertSame('2000942144/2010', (string) $number);
    }

    public function testToStringWithPrefix()
    {
        $number = new AccountNumber('19', '17608231', '0100'); // To je E-ON btw

        $this->assertSame('19-17608231/0100', (string) $number);
    }

    public function testFromString()
    {
        $number = AccountNumber::fromString('2000942144/2010');

        $this->assertNull($number->getPrefix());
        $this->assertSame('2000942144', $number->getNumber());
        $this->assertSame('2010', $number->getBankCode());
    }
}
