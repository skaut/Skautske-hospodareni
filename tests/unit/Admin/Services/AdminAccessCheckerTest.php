<?php

declare(strict_types=1);

namespace App\Model\Admin\Services;

use App\Model\User\Enum\SystemRole;
use App\Model\User\Repository\SystemUserRoleRepository;
use Codeception\Test\Unit;
use Mockery;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;

final class AdminAccessCheckerTest extends Unit
{
    public function testReturnsTrueForPersistentAdminRole(): void
    {
        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldReceive('hasRole')->with(1942, SystemRole::ADMIN)->once()->andReturn(true);

        self::assertTrue((new AdminAccessChecker($this->mockUser(1942), $repository, []))->isCurrentUserAllowed());
    }

    public function testConfiguredFallbackGrantsAdminRole(): void
    {
        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldNotReceive('hasRole');

        $checker = new AdminAccessChecker($this->mockUser(1942), $repository, [1942, '9999']);

        self::assertTrue($checker->isCurrentUserAllowed());
        self::assertSame([1942, 9999], $checker->getConfiguredAdminUserIds());
    }

    public function testSupportCanAccessOnlySupportSection(): void
    {
        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldReceive('hasRole')->with(1942, SystemRole::ADMIN)->twice()->andReturn(false);
        $repository->shouldReceive('hasRole')->with(1942, SystemRole::SUPPORT)->once()->andReturn(true);

        $checker = new AdminAccessChecker($this->mockUser(1942), $repository, []);

        self::assertFalse($checker->isCurrentUserAllowed());
        self::assertTrue($checker->canAccessAdministration());
    }

    public function testReturnsFalseForMissingUserId(): void
    {
        $repository = Mockery::mock(SystemUserRoleRepository::class);
        $repository->shouldNotReceive('hasRole');

        self::assertFalse((new AdminAccessChecker($this->mockUser(null), $repository, [1942]))->canAccessAdministration());
    }

    private function mockUser(?int $userId): User
    {
        $storage = Mockery::mock(UserStorage::class);
        $storage->shouldReceive('getState')
            ->andReturn([$userId !== null, $userId !== null ? new SimpleIdentity($userId) : null, null]);

        return new User($storage);
    }
}
