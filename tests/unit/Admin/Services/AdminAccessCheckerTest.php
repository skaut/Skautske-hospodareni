<?php

declare(strict_types=1);

namespace App\Model\Admin\Services;

use App\Model\User\Repository\AdminUserRepository;
use Codeception\Test\Unit;
use Mockery;
use Nette\Security\IUserStorage;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

final class AdminAccessCheckerTest extends Unit
{
    public function testReturnsTrueForPersistentAdminUserId(): void
    {
        $repository = Mockery::mock(AdminUserRepository::class);
        $repository->shouldReceive('hasUserId')
            ->with(1942)
            ->once()
            ->andReturn(true);

        $checker = new AdminAccessChecker($this->mockUser(1942), $repository, []);

        $this->assertTrue($checker->isCurrentUserAllowed());
    }

    public function testReturnsTrueForConfiguredFallbackUserId(): void
    {
        $repository = Mockery::mock(AdminUserRepository::class);
        $repository->shouldReceive('hasUserId')
            ->with(1942)
            ->once()
            ->andReturn(false);

        $checker = new AdminAccessChecker($this->mockUser(1942), $repository, [1942, '9999']);

        $this->assertTrue($checker->isCurrentUserAllowed());
        $this->assertSame([1942, 9999], $checker->getConfiguredAdminUserIds());
    }

    public function testReturnsFalseForMissingUserId(): void
    {
        $repository = Mockery::mock(AdminUserRepository::class);
        $repository->shouldNotReceive('hasUserId');

        $checker = new AdminAccessChecker($this->mockUser(null), $repository, [1942]);

        $this->assertFalse($checker->isCurrentUserAllowed());
    }

    public function testReturnsFalseForDisallowedUserId(): void
    {
        $repository = Mockery::mock(AdminUserRepository::class);
        $repository->shouldReceive('hasUserId')
            ->with(3333)
            ->once()
            ->andReturn(false);

        $checker = new AdminAccessChecker($this->mockUser(3333), $repository, [1942]);

        $this->assertFalse($checker->isCurrentUserAllowed());
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
