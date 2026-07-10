<?php

declare(strict_types=1);

namespace App\Presentation\Settings;

use App\Presentation\Settings\User\UserPresenter;
use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionMethod;

final class SettingsReadableUnitGuardTest extends Unit
{
    public function testSettingsSectionsRequireReadableUnitByDefault(): void
    {
        $presenter = new class extends SettingsBasePresenter {
        };

        self::assertTrue($this->requiresReadableUnit($presenter));
    }

    public function testUserSettingsDoesNotRequireReadableUnit(): void
    {
        $presenter = (new ReflectionClass(UserPresenter::class))->newInstanceWithoutConstructor();

        self::assertFalse($this->requiresReadableUnit($presenter));
    }

    private function requiresReadableUnit(object $presenter): bool
    {
        $method = new ReflectionMethod($presenter, 'requiresReadableUnit');
        $method->setAccessible(true);

        return $method->invoke($presenter);
    }
}
