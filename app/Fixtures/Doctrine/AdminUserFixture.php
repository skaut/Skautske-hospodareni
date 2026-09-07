<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\User\Entity\SystemUserRole;
use App\Model\User\Enum\SystemRole;
use App\Model\User\Repository\SystemUserRoleRepository;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

final class AdminUserFixture extends AbstractFixture
{
    private const ADMIN_USER_ID = 1942;
    private const CREATED_AT = '2026-03-19 00:00:00';

    public function load(ObjectManager $manager): void
    {
        if (! $manager instanceof \Doctrine\ORM\EntityManagerInterface) {
            return;
        }

        if (! $manager->getConnection()->createSchemaManager()->tablesExist(['system_user_role'])) {
            return;
        }

        $repository = $manager->getRepository(SystemUserRole::class);

        if ($repository instanceof SystemUserRoleRepository && $repository->hasRole(self::ADMIN_USER_ID, SystemRole::ADMIN)) {
            return;
        }

        $manager->persist(new SystemUserRole(self::ADMIN_USER_ID, SystemRole::ADMIN, new DateTimeImmutable(self::CREATED_AT)));
        $manager->flush();
    }
}
