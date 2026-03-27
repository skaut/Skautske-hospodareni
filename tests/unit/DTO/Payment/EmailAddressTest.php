<?php

declare(strict_types=1);

namespace App\Model\DTO\Travel\Command;

use App\Model\Common\EmailAddress;
use Codeception\Test\Unit;
use InvalidArgumentException;

class EmailAddressTest extends Unit
{
    private const VALID_EMAIL = 'test@gmail.com';

    public function testValidation(): void
    {
        $this->assertSame(self::VALID_EMAIL, (new EmailAddress(self::VALID_EMAIL))->getValue());

        $this->assertSame(self::VALID_EMAIL, (new EmailAddress(' '.self::VALID_EMAIL.' '))->getValue());

        $this->expectException(InvalidArgumentException::class);
        new EmailAddress('a.cz');
    }
}
