<?php

declare(strict_types=1);

namespace App\Model\User\ReadModel\QueryHandlers;

use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\SkautisRole;
use Mockery as m;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;

final class ActiveSkautisRoleQueryHandlerTest extends \Codeception\Test\Unit
{
    public function testReturnsCurrentRoleFromIdentity(): void
    {
        $role = new SkautisRole('hospodarStredisko', 'Hospodář - Středisko', 100, 'Středisko');
        $handler = new ActiveSkautisRoleQueryHandler($this->userWithIdentity(new SimpleIdentity(1, data: ['currentRole' => $role])));

        self::assertSame($role, $handler(new ActiveSkautisRoleQuery()));
    }

    public function testIdentityWithoutCurrentRoleThrowsMissingCurrentRole(): void
    {
        $handler = new ActiveSkautisRoleQueryHandler($this->userWithIdentity(new SimpleIdentity(1)));

        $this->expectException(MissingCurrentRole::class);

        $handler(new ActiveSkautisRoleQuery());
    }

    private function userWithIdentity(SimpleIdentity $identity): User
    {
        $storage = m::mock(UserStorage::class);
        $storage->shouldReceive('getState')->andReturn([true, $identity, null]);

        return new User($storage);
    }
}
