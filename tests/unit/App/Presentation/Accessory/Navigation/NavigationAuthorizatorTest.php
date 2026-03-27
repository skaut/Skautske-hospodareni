<?php

declare(strict_types=1);

namespace App\Presentation\Accessory\Navigation;

use App\Model\Auth\IAuthorizator as ApplicationAuthorizator;
use App\Model\Auth\Resources\Admin;
use Codeception\Test\Unit;
use Contributte\MenuControl\IMenuItem;
use Mockery;

final class NavigationAuthorizatorTest extends Unit
{
    public function testAllowsMenuItemsWithoutAdminRequirement(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldNotReceive('isAllowed');

        $item = Mockery::mock(IMenuItem::class);
        $item->shouldReceive('getData')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);

        self::assertTrue((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testDelegatesAdminOnlyMenuItemsToApplicationAuthorizator(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(Admin::ACCESS, null)
            ->once()
            ->andReturn(true);

        $item = Mockery::mock(IMenuItem::class);
        $item->shouldReceive('getData')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(true);

        self::assertTrue((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testTreatsTruthyMenuDataAsAdminRequirement(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(Admin::ACCESS, null)
            ->once()
            ->andReturn(false);

        $item = Mockery::mock(IMenuItem::class);
        $item->shouldReceive('getData')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(1);

        self::assertFalse((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }
}
