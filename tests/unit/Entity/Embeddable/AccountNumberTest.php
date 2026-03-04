<?php

declare(strict_types=1);

namespace Entity\Embeddable;

use Codeception\Test\Unit;

final class AccountNumberTest extends Unit
{
    public function testValidatesKnownGoodAccountUsedInTests(): void
    {
        self::assertTrue(AccountNumber::validateParts(null, '2000942144', '2010'));
        self::assertTrue(AccountNumber::isValid('2000942144/2010'));
    }

    public function testRejectsSyntheticAccountsThatFrontendMustNotAccept(): void
    {
        self::assertFalse(AccountNumber::validateParts('19', '1234567890', '2010'));
        self::assertFalse(AccountNumber::validateParts(null, '9876543210', '0300'));
    }
}
