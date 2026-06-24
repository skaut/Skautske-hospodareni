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
