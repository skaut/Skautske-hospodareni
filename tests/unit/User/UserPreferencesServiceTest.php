<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\User\Entity\UserPreference;
use App\Model\User\Manager\UserPreferenceManager;
use App\Model\User\Repository\UserPreferenceRepository;
use Codeception\Test\Unit;
use Mockery;
use Nette\Security\IUserStorage;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use RuntimeException;

final class UserPreferencesServiceTest extends Unit
{
    public function testHelpIsShownByDefaultWhenPreferenceDoesNotExist(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(null);

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldNotReceive('saveHelpVisibility');

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);

        self::assertTrue($service->shouldShowHelp());
    }

    public function testStoredPreferenceCanHideHelp(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(new UserPreference(1942, false));

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldNotReceive('saveHelpVisibility');

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);

        self::assertFalse($service->shouldShowHelp());
    }

    public function testSkautisLoginExtensionIsDisabledByDefault(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(null);

        $manager = Mockery::mock(UserPreferenceManager::class);

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);

        self::assertFalse($service->shouldExtendSkautisLogin());
    }

    public function testStoredPreferenceCanEnableSkautisLoginExtension(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(new UserPreference(1942, true, true));

        $manager = Mockery::mock(UserPreferenceManager::class);

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);

        self::assertTrue($service->shouldExtendSkautisLogin());
    }

    public function testHelpRemainsVisibleBeforePreferenceMigrationIsAvailable(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andThrow(new RuntimeException('Preference storage is not available.'));

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldNotReceive('saveHelpVisibility');

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);

        self::assertTrue($service->shouldShowHelp());
    }

    public function testHelpVisibilityIsSavedForCurrentUser(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldNotReceive('findOneByUserId');

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldReceive('saveHelpVisibility')
            ->with(1942, false)
            ->once()
            ->andReturn(new UserPreference(1942, false));

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);
        $service->setShowHelp(false);
    }

    public function testPreferencesAreSavedForCurrentUser(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldNotReceive('findOneByUserId');

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldReceive('savePreferences')
            ->with(1942, false, true, true)
            ->once()
            ->andReturn(new UserPreference(1942, false, true, true));
        $manager->shouldReceive('saveRememberedSkautisRole')
            ->with(1942, 456)
            ->once()
            ->andReturn(new UserPreference(1942, false, true, true, 456));

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);
        $service->setPreferences(false, true, true, 456);
    }

    public function testSkautisRoleRememberingIsDisabledByDefault(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(null);

        $manager = Mockery::mock(UserPreferenceManager::class);

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);

        self::assertFalse($service->shouldRememberSkautisRole());
    }

    public function testRememberedSkautisRoleIsReturnedWhenAvailable(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(new UserPreference(1942, true, false, true, 456));

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldNotReceive('clearRememberedSkautisRole');

        $service = new UserPreferencesService($this->mockUser(null), $repository, $manager);

        self::assertSame(456, $service->getRememberedSkautisRoleIdForLogin(1942, [123, 456]));
    }

    public function testUnavailableRememberedSkautisRoleIsCleared(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(new UserPreference(1942, true, false, true, 456));

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldReceive('clearRememberedSkautisRole')
            ->with(1942)
            ->once()
            ->andReturn(new UserPreference(1942, true, false, true));

        $service = new UserPreferencesService($this->mockUser(null), $repository, $manager);

        self::assertNull($service->getRememberedSkautisRoleIdForLogin(1942, [123]));
    }

    public function testChangedSkautisRoleIsRememberedOnlyWhenEnabled(): void
    {
        $repository = Mockery::mock(UserPreferenceRepository::class);
        $repository->shouldReceive('findOneByUserId')
            ->with(1942)
            ->once()
            ->andReturn(new UserPreference(1942, true, false, true));

        $manager = Mockery::mock(UserPreferenceManager::class);
        $manager->shouldReceive('saveRememberedSkautisRole')
            ->with(1942, 456)
            ->once()
            ->andReturn(new UserPreference(1942, true, false, true, 456));

        $service = new UserPreferencesService($this->mockUser(1942), $repository, $manager);
        $service->rememberCurrentSkautisRole(456);
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
