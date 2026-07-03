<?php

declare(strict_types=1);

namespace App\Model\Common\Embeddable;

use Codeception\Test\Unit;
use ReflectionClass;

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

    public function testReflectionCreatedEmbeddableHasInitializedProperties(): void
    {
        $accountNumber = (new ReflectionClass(AccountNumber::class))->newInstanceWithoutConstructor();
        $reflection = new ReflectionClass($accountNumber);

        self::assertTrue($reflection->getProperty('prefix')->isInitialized($accountNumber));
        self::assertTrue($reflection->getProperty('number')->isInitialized($accountNumber));
        self::assertTrue($reflection->getProperty('bankCode')->isInitialized($accountNumber));
        self::assertTrue($reflection->getProperty('bankName')->isInitialized($accountNumber));
        self::assertTrue($reflection->getProperty('iban')->isInitialized($accountNumber));
        self::assertTrue($reflection->getProperty('bic')->isInitialized($accountNumber));
    }
}
