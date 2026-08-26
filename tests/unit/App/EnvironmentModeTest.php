<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;

final class EnvironmentModeTest extends Unit
{
    public function testResolvesVisibleEnvironmentModes(): void
    {
        self::assertSame(EnvironmentMode::DEVELOPMENT, EnvironmentMode::fromAppEnv('dev'));
        self::assertSame(EnvironmentMode::TEST, EnvironmentMode::fromAppEnv('test'));
        self::assertSame(EnvironmentMode::BETA, EnvironmentMode::fromAppEnv('beta'));
        self::assertSame(EnvironmentMode::PRODUCTION, EnvironmentMode::fromAppEnv('prod'));
    }

    public function testUnknownEnvironmentIsSafelyMarkedAsTest(): void
    {
        self::assertSame(EnvironmentMode::TEST, EnvironmentMode::fromAppEnv('staging'));
        self::assertTrue(EnvironmentMode::fromAppEnv('staging')->shouldShowBadge());
    }
}
