<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use Codeception\Test\Unit;
use DateTimeImmutable;

final class BankTransactionKeyGeneratorTest extends Unit
{
    public function testFioKeyUsesNativeTransactionId(): void
    {
        $generator = new BankTransactionKeyGenerator();

        self::assertSame('123456', $generator->fromFio('123456'));
    }

    public function testGpcKeyIsDeterministicAndNamespaced(): void
    {
        $generator = new BankTransactionKeyGenerator();

        $first = $generator->fromGpc(
            '8310192897/2010',
            new DateTimeImmutable('2026-02-28 12:00:00'),
            -24.20,
            null,
            'Najem kancelari 1/26',
            null,
            8402,
            'Najem kancelari 1/26',
        );
        $second = $generator->fromGpc(
            '8310192897/2010',
            new DateTimeImmutable('2026-02-28 12:00:00'),
            -24.20,
            null,
            'Najem kancelari 1/26',
            null,
            8402,
            'Najem kancelari 1/26',
        );

        self::assertSame($first, $second);
        self::assertStringStartsWith('gpc:', $first);
    }
}
