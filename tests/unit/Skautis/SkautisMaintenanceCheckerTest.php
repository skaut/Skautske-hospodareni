<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use Codeception\Test\Unit;
use Mockery;
use Nette\Caching\Storages\MemoryStorage;
use RuntimeException;
use Skautis\Skautis;

final class SkautisMaintenanceCheckerTest extends Unit
{
    public function testSkautisFailureDoesNotBreakApplicationRequest(): void
    {
        $skautis = Mockery::mock(Skautis::class);
        $skautis->shouldReceive('isMaintenance')
            ->once()
            ->andThrow(new RuntimeException('SkautIS health check timed out.'));

        $checker = new SkautisMaintenanceChecker($skautis, new MemoryStorage());

        self::assertFalse($checker->isMaintenance());
    }

    public function testSkautisWarningDoesNotBreakApplicationRequest(): void
    {
        $skautis = Mockery::mock(Skautis::class);
        $skautis->shouldReceive('isMaintenance')
            ->once()
            ->andReturnUsing(static function (): bool {
                trigger_error('DNS lookup failed.', E_USER_WARNING);

                return true;
            });

        $checker = new SkautisMaintenanceChecker($skautis, new MemoryStorage());

        self::assertFalse($checker->isMaintenance());
    }
}
