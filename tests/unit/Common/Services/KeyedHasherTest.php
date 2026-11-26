<?php

declare(strict_types=1);

namespace App\Model\Common\Services;

use Codeception\Test\Unit as TestCase;

use function hash;

final class KeyedHasherTest extends TestCase
{
    public function testSameValueGetsTheSameHashAndAnotherValueAnotherOne(): void
    {
        $hasher = new KeyedHasher('secret');

        self::assertSame($hasher->hash('user', 42), $hasher->hash('user', 42));
        self::assertNotSame($hasher->hash('user', 42), $hasher->hash('user', 43));
        self::assertMatchesRegularExpression('~^[0-9a-f]{64}$~', (string) $hasher->hash('user', 42));
    }

    /** Without the key the hash of a small number could be found by trying every value. */
    public function testHashDependsOnTheKey(): void
    {
        self::assertNotSame((new KeyedHasher('one'))->hash('user', 42), (new KeyedHasher('two'))->hash('user', 42));
        self::assertNotSame(hash('sha256', '42'), (new KeyedHasher('one'))->hash('user', 42));
    }

    /** One key serves every use, the purpose keeps their hashes apart. */
    public function testSameValueOfAnotherPurposeGetsAnotherHash(): void
    {
        $hasher = new KeyedHasher('secret');

        self::assertNotSame($hasher->hash('user', 42), $hasher->hash('unit', 42));
    }

    public function testWithoutKeyNothingIsHashed(): void
    {
        foreach ([null, ''] as $secret) {
            $hasher = new KeyedHasher($secret);

            self::assertFalse($hasher->isConfigured());
            self::assertNull($hasher->hash('user', 42));
        }
    }
}
