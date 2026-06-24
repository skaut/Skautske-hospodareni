<?php

declare(strict_types=1);

namespace App\Model\Auth;

use App\Model\Admin\Services\AdminAccessChecker;
use App\Model\Auth\Resources\Admin;
use App\Model\Auth\Resources\InvoiceAccess;
use App\Model\Auth\Resources\Unit as UnitResource;
use App\Model\Invoice\InvoiceAccessChecker;
use App\Model\Skautis\Auth\SkautisAuthorizator;
use App\Model\User\Repository\AdminUserRepository;
use App\Model\User\Repository\InvoiceAccessUserRepository;
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
        $repository = Mockery::mock(AdminUserRepository::class);
        $repository->shouldReceive('hasUserId')
            ->with(1942)
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

        $adminRepository = Mockery::mock(AdminUserRepository::class);
        $adminRepository->shouldNotReceive('hasUserId');

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
                'ID_Action' => null,
            ])
            ->andReturn([$allowedAction]);

        $repository = Mockery::mock(AdminUserRepository::class);
        $repository->shouldNotReceive('hasUserId');

        $adminAccessChecker = new AdminAccessChecker($this->mockUser(null), $repository, []);
        $authorizator = new CompositeAuthorizator(
            new SkautisAuthorizator($webservice),
            $adminAccessChecker,
            $this->invoiceAccessChecker(),
        );

        self::assertTrue($authorizator->isAllowed(UnitResource::EDIT, 123));
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
