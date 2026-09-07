<?php

declare(strict_types=1);

namespace Admin;

use App\Model\User\Entity\SystemUserRole;
use App\Model\User\Enum\SystemRole;
use App\Model\User\Manager\SystemUserRoleManager;
use App\Model\User\Repository\SystemUserRoleRepository;
use IntegrationTest;

final class SystemUserRoleManagerTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [SystemUserRole::class];
    }

    public function testManagerReplacesAndRemovesRolesForUser(): void
    {
        $repository = new SystemUserRoleRepository($this->entityManager);
        $manager = new SystemUserRoleManager($this->entityManager, $repository);

        $manager->replaceRoles(1942, [SystemRole::ADMIN, SystemRole::SUPPORT]);

        self::assertTrue($repository->hasRole(1942, SystemRole::ADMIN));
        self::assertTrue($repository->hasRole(1942, SystemRole::SUPPORT));

        $manager->replaceRoles(1942, [SystemRole::SUPPORT]);

        self::assertFalse($repository->hasRole(1942, SystemRole::ADMIN));
        self::assertTrue($repository->hasRole(1942, SystemRole::SUPPORT));

        $manager->removeAllForUser(1942);

        self::assertFalse($repository->hasUserId(1942));
    }

    public function testRepositoryGroupsRolesByUserId(): void
    {
        $repository = new SystemUserRoleRepository($this->entityManager);
        $this->entityManager->persist(new SystemUserRole(3000, SystemRole::SUPPORT));
        $this->entityManager->persist(new SystemUserRole(1942, SystemRole::ADMIN));
        $this->entityManager->persist(new SystemUserRole(1942, SystemRole::SUPPORT));
        $this->entityManager->flush();

        self::assertSame([
            1942 => [SystemRole::ADMIN, SystemRole::SUPPORT],
            3000 => [SystemRole::SUPPORT],
        ], $repository->findAllGroupedByUserId());
    }
}
