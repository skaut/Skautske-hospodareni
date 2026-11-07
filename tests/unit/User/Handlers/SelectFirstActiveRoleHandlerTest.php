<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use App\Model\Unit\UnitService;
use App\Model\User\SkautisRole;
use App\Model\User\UserService;
use Codeception\Test\Unit;
use Mockery as m;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;
use RuntimeException;

final class SelectFirstActiveRoleHandlerTest extends Unit
{
    public function testSelectsFirstRoleAndStoresItInIdentityWithSingleRoleLoad(): void
    {
        $roles = [
            (object) ['ID' => 10, 'Key' => 'hospodarStredisko', 'DisplayName' => 'Hospodář - Středisko', 'ID_Unit' => 100, 'Unit' => 'Středisko'],
            (object) ['ID' => 20, 'Key' => 'vedouciOddil', 'DisplayName' => 'Vedoucí - Oddíl', 'ID_Unit' => 200, 'Unit' => 'Oddíl'],
        ];
        $userService = m::mock(UserService::class)->makePartial();
        $userService->shouldReceive('getAllSkautisRoles')->once()->andReturn($roles);
        $unitService = m::mock(UnitService::class);
        $access = [UserService::ACCESS_READ => [], UserService::ACCESS_EDIT => []];
        $userService->shouldReceive('getAccessArrays')->once()->with($unitService, $roles, 10)->ordered()->andReturn($access);
        $userService->shouldReceive('updateSkautISRole')->once()->with(10)->ordered();
        $userService->shouldReceive('getRoleId')->andReturn(10);

        $identity = new SimpleIdentity(1);
        $storage = m::mock(UserStorage::class);
        $storage->shouldReceive('getState')->andReturn([true, $identity, null]);

        (new SelectFirstActiveRoleHandler($userService, $unitService, new User($storage)))(new SelectFirstActiveRole());

        self::assertEquals(new SkautisRole('hospodarStredisko', 'Hospodář - Středisko', 100, 'Středisko'), $identity->currentRole);
        self::assertSame($roles, $identity->skautisRoles);
        self::assertSame($access, $identity->access);
    }

    public function testFailedAccessLoadLeavesRoleUnchangedHereAndInSkautis(): void
    {
        $roles = [(object) ['ID' => 10, 'Key' => 'hospodarStredisko', 'DisplayName' => 'Hospodář - Středisko', 'ID_Unit' => 100, 'Unit' => 'Středisko']];
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getAllSkautisRoles')->andReturn($roles);
        $userService->shouldReceive('getAccessArrays')->andThrow(new RuntimeException('SkautIS failed'));
        $userService->shouldNotReceive('updateSkautISRole');
        $identity = new SimpleIdentity(1, [], ['currentRole' => 'old', 'access' => 'old']);
        $storage = m::mock(UserStorage::class);
        $storage->shouldReceive('getState')->andReturn([true, $identity, null]);

        try {
            (new SelectFirstActiveRoleHandler($userService, m::mock(UnitService::class), new User($storage)))(new SelectFirstActiveRole());
            self::fail('RuntimeException expected.');
        } catch (RuntimeException) {
        }

        self::assertSame('old', $identity->currentRole);
        self::assertSame('old', $identity->access);
    }
}
