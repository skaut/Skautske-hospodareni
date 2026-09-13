<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use Codeception\Test\Unit;

use function serialize;
use function unserialize;

/**
 * Hlídá důvod, proč je Doctrine second-level cache vypnutá.
 *
 * Consistence enumy jsou singletony a {@see \Consistence\Enum\Enum::equals()} porovnává identitou.
 * Cache entity ukládá serializací, takže hydratovaný Category::$operationType je nová instance
 * a všechna porovnání přes equals() začnou vracet false — v praxi prázdné selecty kategorií
 * ve formuláři dokladu.
 *
 * Až budou enumy nativní (nativní case přežije unserializaci jako tatáž instance), tenhle test
 * začne selhat — a to je signál, že second-level cache se dá znovu zapnout.
 * Viz {@see \App\Model\Infrastructure\EntityManagerFactory::create()}.
 */
final class OperationSerializationTest extends Unit
{
    public function testSerializationRoundTripLosesSingletonIdentity(): void
    {
        $original = Operation::get(Operation::EXPENSE);
        $restored = unserialize(serialize($original));

        self::assertInstanceOf(Operation::class, $restored);
        self::assertNotSame($original, $restored);
        self::assertFalse($original->equals($restored));
        self::assertTrue($restored->equalsValue(Operation::EXPENSE));
    }
}
