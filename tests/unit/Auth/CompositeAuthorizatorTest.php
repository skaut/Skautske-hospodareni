<?php

declare(strict_types=1);

namespace App\Model\Auth;

use App\Model\Admin\Services\AdminAccessChecker;
use App\Model\Auth\Resources\Admin;
use App\Model\Auth\Resources\BugReports;
use App\Model\Auth\Resources\Event as EventResource;
use App\Model\Auth\Resources\InvoiceAccess;
use App\Model\Auth\Resources\Unit as UnitResource;
use App\Model\Invoice\InvoiceAccessChecker;
use App\Model\Skautis\Auth\SkautisAuthorizator;
use App\Model\User\Enum\SystemRole;
use App\Model\User\Repository\InvoiceAccessUserRepository;
use App\Model\User\Repository\SystemUserRoleRepository;
use Codeception\Test\Unit;
use Mockery;
use Nette\Security\IUserStorage;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

final class CompositeAuthorizatorTest extends Unit
{
    public function testReturnsTrueForAdminAccessWhenAdminCheckerAllowsCurrentUser(): void
    {
        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldReceive('hasRole')
            ->with(1942, SystemRole::ADMIN)
            ->once()
            ->andReturn(true);

        $webservice = Mockery::mock(WebServiceInterface::class);
        $webservice->shouldNotReceive('ActionVerify');

        $adminAccessChecker = new AdminAccessChecker($this->mockUser(1942), $repository, []);
        $authorizator = new CompositeAuthorizator(
            new SkautisAuthorizator($webservice),
            $adminAccessChecker,
            $this->invoiceAccessChecker(),
        );

        self::assertTrue($authorizator->isAllowed(Admin::ACCESS, null));
    }

    public function testReturnsTrueForInvoiceAccessWhenInvoiceCheckerAllowsCurrentUser(): void
    {
        $webservice = Mockery::mock(WebServiceInterface::class);
        $webservice->shouldNotReceive('ActionVerify');

        $adminRepository = Mockery::mock(SystemUserRoleRepository::class);
        $adminRepository->shouldNotReceive('hasRole');

        $invoiceRepository = Mockery::mock(InvoiceAccessUserRepository::class);
        $invoiceRepository->shouldReceive('hasUserId')
            ->with(1942)
            ->once()
            ->andReturn(true);

        $authorizator = new CompositeAuthorizator(
            new SkautisAuthorizator($webservice),
            new AdminAccessChecker($this->mockUser(null), $adminRepository, []),
            new InvoiceAccessChecker($this->mockUser(1942), $invoiceRepository, []),
        );

        self::assertTrue($authorizator->isAllowed(InvoiceAccess::ACCESS, null));
    }

    public function testDelegatesNonAdminActionsToSkautisAuthorizator(): void
    {
        $allowedAction = new stdClass();
        $allowedAction->ID = UnitResource::EDIT[1];

        $webservice = Mockery::mock(WebServiceInterface::class);
        $webservice->shouldReceive('ActionVerify')
            ->once()
            ->with([
                'ID' => 123,
                'ID_Table' => 'OU_Unit',
            ])
            ->andReturn([$allowedAction]);

        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldNotReceive('hasRole');

        $adminAccessChecker = new AdminAccessChecker($this->mockUser(null), $repository, []);
        $authorizator = new CompositeAuthorizator(
            new SkautisAuthorizator($webservice),
            $adminAccessChecker,
            $this->invoiceAccessChecker(),
        );

        self::assertTrue($authorizator->isAllowed(UnitResource::EDIT, 123));
    }

    public function testSkautisAuthorizatorOmitsOptionalNullSoapArguments(): void
    {
        $allowedAction = new stdClass();
        $allowedAction->ID = EventResource::CREATE[1];

        $webservice = Mockery::mock(WebServiceInterface::class);
        $webservice->shouldReceive('ActionVerify')
            ->once()
            ->with([
                'ID_Table' => 'EV_EventGeneral',
            ])
            ->andReturn([$allowedAction]);

        $authorizator = new SkautisAuthorizator($webservice);

        self::assertTrue($authorizator->isAllowed(EventResource::CREATE, null));
    }

    public function testSupportCanAccessBugReportsButNotFullAdmin(): void
    {
        $webservice = Mockery::mock(WebServiceInterface::class);
        $webservice->shouldNotReceive('ActionVerify');
        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldReceive('hasRole')->with(1942, SystemRole::ADMIN)->times(3)->andReturn(false);
        $repository->shouldReceive('hasRole')->with(1942, SystemRole::SUPPORT)->twice()->andReturn(true);

        $authorizator = new CompositeAuthorizator(
            new SkautisAuthorizator($webservice),
            new AdminAccessChecker($this->mockUser(1942), $repository, []),
            $this->invoiceAccessChecker(),
        );

        self::assertFalse($authorizator->isAllowed(Admin::ACCESS, null));
        self::assertTrue($authorizator->isAllowed(Admin::ANY_ACCESS, null));
        self::assertTrue($authorizator->isAllowed(BugReports::ACCESS, null));
    }

    private function invoiceAccessChecker(): InvoiceAccessChecker
    {
        $repository = Mockery::mock(InvoiceAccessUserRepository::class);
        $repository->shouldNotReceive('hasUserId');

        return new InvoiceAccessChecker($this->mockUser(null), $repository, []);
    }

    private function mockUser(?int $userId): User
    {
        $storage = Mockery::mock(IUserStorage::class);
        $storage->shouldReceive('isAuthenticated')
            ->andReturn($userId !== null);
        $storage->shouldReceive('getIdentity')
            ->andReturn($userId !== null ? new SimpleIdentity($userId) : null);
        $storage->shouldReceive('getLogoutReason')
            ->andReturn(null);

        return new User($storage);
    }
}
