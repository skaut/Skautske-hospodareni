<?php

declare(strict_types=1);

namespace App\Model\Common\Embeddable;

use Codeception\Test\Unit;
use ReflectionClass;

final class TransactionTest extends Unit
{
    public function testReflectionCreatedEmbeddableHasInitializedProperties(): void
    {
        $transaction = (new ReflectionClass(Transaction::class))->newInstanceWithoutConstructor();
        $reflection = new ReflectionClass($transaction);

        self::assertTrue($reflection->getProperty('id')->isInitialized($transaction));
        self::assertTrue($reflection->getProperty('bankAccount')->isInitialized($transaction));
        self::assertTrue($reflection->getProperty('payer')->isInitialized($transaction));
        self::assertTrue($reflection->getProperty('note')->isInitialized($transaction));
        self::assertTrue($reflection->getProperty('date')->isInitialized($transaction));
    }
}
