<?php

declare(strict_types=1);

namespace Model\DTO\Travel\Command;

use Codeception\Test\Unit;
use InvalidArgumentException;
use Model\Common\EmailAddress;

class EmailAddressTest extends Unit
{
    private const VALID_EMAIL = 'test@gmail.com';

    public function testValidation() : void
    {
        $this->assertSame(self::VALID_EMAIL, (new EmailAddress(self::VALID_EMAIL))->getValue());

        $this->assertSame(self::VALID_EMAIL, (new EmailAddress(' ' . self::VALID_EMAIL . ' '))->getValue());

        $this->expectException(InvalidArgumentException::class);
        new EmailAddress('a.cz');
    }
}
