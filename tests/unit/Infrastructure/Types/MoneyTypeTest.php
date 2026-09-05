<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery as m;

final class MoneyTypeTest extends Unit
{
    public function testDatabaseMinorUnitsAreHydratedWithoutDecimalConversion(): void
    {
        $type = new MoneyType();
        $platform = m::mock(AbstractPlatform::class);

        $money = $type->convertToPHPValue(-19, $platform);

        self::assertNotNull($money);
        self::assertSame('-19', $money->getAmount());
        self::assertSame('-0.19', MoneyFactory::toDecimal($money));
    }

    public function testMoneyIsPersistedAsMinorUnits(): void
    {
        $type = new MoneyType();
        $platform = m::mock(AbstractPlatform::class);

        self::assertSame('30', $type->convertToDatabaseValue(MoneyFactory::fromDecimal('0.30'), $platform));
    }

    public function testNullMoneyIsPersistedAsNull(): void
    {
        $type = new MoneyType();
        $platform = m::mock(AbstractPlatform::class);

        self::assertNull($type->convertToDatabaseValue(null, $platform));
    }
}
