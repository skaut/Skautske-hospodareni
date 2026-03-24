<?php

declare(strict_types=1);

namespace Admin;

use App\Model\User\Entity\AdminUser;
use IntegrationTest;
use App\Model\User\Manager\AdminUserManager;
use App\Model\User\Repository\AdminUserRepository;

final class AdminUserManagerTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [AdminUser::class];
    }

    public function testManagerCanCreateUpdateAndDeleteAdminUser(): void
    {
        $repository = new AdminUserRepository($this->entityManager);
        $manager = new AdminUserManager($this->entityManager);

        $adminUser = $manager->create(1942);

        $this->assertTrue($adminUser->hasId());
        $this->assertTrue($repository->hasUserId(1942));

        $manager->updateUserId($adminUser, 2465);

        $this->assertFalse($repository->hasUserId(1942));
        $this->assertTrue($repository->hasUserId(2465));

        $manager->delete($adminUser);

        $this->assertSame([], $repository->findAllOrderedByUserId());
    }

    public function testRepositoryReturnsAdminsOrderedByUserId(): void
    {
        $repository = new AdminUserRepository($this->entityManager);

        $first = new AdminUser(3000);
        $second = new AdminUser(1942);

        $this->entityManager->persist($first);
        $this->entityManager->persist($second);
        $this->entityManager->flush();

        $adminUsers = $repository->findAllOrderedByUserId();

        $this->assertCount(2, $adminUsers);
        $this->assertSame(1942, $adminUsers[0]->getUserId());
        $this->assertSame(3000, $adminUsers[1]->getUserId());
    }
}
