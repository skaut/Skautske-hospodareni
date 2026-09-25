<?php

declare(strict_types=1);

namespace App\Presentation\Accessory\Navigation;

use App\Model\Auth\IAuthorizator as ApplicationAuthorizator;
use App\Model\Auth\Resources\Admin;
use App\Model\Auth\Resources\BugReports;
use App\Model\Auth\Resources\InvoiceAccess;
use Codeception\Test\Unit;
use Contributte\MenuControl\IMenuItem;
use Mockery;

final class NavigationAuthorizatorTest extends Unit
{
    public function testAllowsMenuItemsWithoutAdminRequirement(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldNotReceive('isAllowed');

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
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

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(true);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(false);

        self::assertTrue((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testTreatsTruthyMenuDataAsAdminRequirement(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(Admin::ACCESS, null)
            ->once()
            ->andReturn(false);

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(1);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(false);

        self::assertFalse((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testDelegatesAdminAreaMenuItemsToApplicationAuthorizator(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(Admin::ANY_ACCESS, null)
            ->once()
            ->andReturn(true);

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(true);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(false);

        self::assertTrue((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testRejectsAdminAreaMenuItemsWithoutApplicationAuthorization(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(Admin::ANY_ACCESS, null)
            ->once()
            ->andReturn(false);

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(true);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(false);

        self::assertFalse((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testDelegatesBugReportsMenuItemsToApplicationAuthorizator(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(BugReports::ACCESS, null)
            ->once()
            ->andReturn(true);

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(true);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(false);

        self::assertTrue((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testRejectsBugReportsMenuItemsWithoutApplicationAuthorization(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(BugReports::ACCESS, null)
            ->once()
            ->andReturn(false);

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(true);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(false);

        self::assertFalse((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testDelegatesInvoiceMenuItemsToApplicationAuthorizator(): void
    {
        $authorizator = Mockery::mock(ApplicationAuthorizator::class);
        $authorizator->shouldReceive('isAllowed')
            ->with(InvoiceAccess::ACCESS, null)
            ->once()
            ->andReturn(true);

        $item = $this->mockItem();
        $item->shouldReceive('getDataItem')
            ->with('requiresAdmin', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresAdminArea', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresBugReportsAccess', false)
            ->once()
            ->andReturn(false);
        $item->shouldReceive('getDataItem')
            ->with('requiresInvoiceAccess', false)
            ->once()
            ->andReturn(true);

        self::assertTrue((new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
    }

    public function testAnnouncementMenuRequiresAnnouncementAccess(): void
    {
        foreach ([true, false] as $allowed) {
            $authorizator = Mockery::mock(ApplicationAuthorizator::class);
            $authorizator->shouldReceive('isAllowed')
                ->with(Admin::ANNOUNCEMENTS_ACCESS, null)
                ->once()
                ->andReturn($allowed);

            $item = $this->mockItem(true);
            foreach (['requiresAdmin', 'requiresAdminArea', 'requiresBugReportsAccess', 'requiresInvoiceAccess'] as $key) {
                $item->shouldReceive('getDataItem')->with($key, false)->once()->andReturn(false);
            }

            self::assertSame($allowed, (new NavigationAuthorizator($authorizator))->isMenuItemAllowed($item));
        }
    }

    private function mockItem(bool $requiresAnnouncementsAccess = false): IMenuItem&Mockery\MockInterface
    {
        $item = Mockery::mock(IMenuItem::class);
        $item->shouldReceive('getDataItem')
            ->with('requiresAnnouncementsAccess', false)
            ->once()
            ->andReturn($requiresAnnouncementsAccess);

        return $item;
    }
}
