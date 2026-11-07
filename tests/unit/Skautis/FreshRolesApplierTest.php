<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use App\Model\Common\Services\CommandBus;
use App\Model\User\Exception\UserHasNoRole;
use App\Model\User\SkautisRole;
use App\Model\User\UserService;
use Codeception\Test\Unit;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Nette\Security\SimpleIdentity;
use Psr\Clock\ClockInterface;
use Psr\Log\NullLogger;
use Skautis\User;
use Skautis\Wsdl\WebServiceInterface;
use Skautis\Wsdl\WsdlManager;
use stdClass;

final class FreshRolesApplierTest extends Unit
{
    private const LOGIN_ID = 'login-id';

    private MemoryStorage $storage;

    private SkautisLoginRefresher $refresher;

    private UserService&MockInterface $userService;

    private CommandBus&MockInterface $commandBus;

    private FreshRolesApplier $applier;

    private SimpleIdentity $identity;

    protected function _before(): void
    {
        $user = new User(Mockery::mock(WsdlManager::class));
        $user->setLoginData(self::LOGIN_ID, 1, 2, new DateTime('2026-11-04 12:30:00'));
        $this->storage = new MemoryStorage();
        $this->refresher = new SkautisLoginRefresher($user, Mockery::mock(WebServiceInterface::class), $this->storage, Mockery::mock(ClockInterface::class), new NullLogger());
        $this->userService = Mockery::mock(UserService::class);
        $this->commandBus = Mockery::mock(CommandBus::class);
        $this->applier = new FreshRolesApplier($this->refresher, $this->userService, $this->commandBus);
        $this->identity = new SimpleIdentity(1, [], ['skautisRoles' => ['old'], 'currentRole' => 'old']);
    }

    public function testNothingHappensWithoutFreshRoles(): void
    {
        $this->userService->shouldNotReceive('getActualRole');
        $this->commandBus->shouldNotReceive('handle');

        self::assertFalse($this->applier->apply($this->identity));
        self::assertSame(['old'], $this->identity->skautisRoles);
    }

    public function testFreshRolesWithCurrentRoleAreApplied(): void
    {
        $roles = $this->storeFreshRoles();
        $role = new SkautisRole('hospodarStredisko', 'Hospodář - Středisko', 100, 'Středisko');
        $this->userService->shouldReceive('getActualRole')->once()->with($roles)->andReturn($role);
        $this->commandBus->shouldNotReceive('handle');

        self::assertFalse($this->applier->apply($this->identity));
        self::assertSame($roles, $this->identity->skautisRoles);
        self::assertSame($role, $this->identity->currentRole);
        self::assertNull($this->refresher->freshRoles());
    }

    public function testEndedRoleIsSwitched(): void
    {
        $this->storeFreshRoles();
        $this->userService->shouldReceive('getActualRole')->once()->andReturnNull();
        $this->commandBus->shouldReceive('handle')->once()->with(Mockery::type(SelectFirstActiveRole::class));

        self::assertTrue($this->applier->apply($this->identity));
        self::assertNull($this->refresher->freshRoles());
    }

    public function testUserWithoutActiveRoleKeepsFreshRolesForRetry(): void
    {
        $roles = $this->storeFreshRoles();
        $this->userService->shouldReceive('getActualRole')->once()->andReturnNull();
        $this->commandBus->shouldReceive('handle')->once()->andThrow(new UserHasNoRole());

        try {
            $this->applier->apply($this->identity);
            self::fail('UserHasNoRole expected.');
        } catch (UserHasNoRole) {
        }

        self::assertEquals($roles, $this->refresher->freshRoles());
        self::assertSame(['old'], $this->identity->skautisRoles);
        self::assertSame('old', $this->identity->currentRole);
    }

    /** @return stdClass[] */
    private function storeFreshRoles(): array
    {
        $roles = [(object) ['ID' => 10, 'Key' => 'hospodarStredisko', 'DisplayName' => 'Hospodář - Středisko', 'ID_Unit' => 100, 'Unit' => 'Středisko']];
        // What SkautisLoginRefresher::extend() caches after a successful background extension.
        (new Cache($this->storage, 'skautis.login'))->save('roles.'.self::LOGIN_ID, $roles);

        return $roles;
    }
}
