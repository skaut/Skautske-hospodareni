<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use App\Model\User\Entity\AdminUser;
use App\Model\User\Repository\AdminUserRepository;

final class AdminUserFixture extends AbstractFixture
{
    private const ADMIN_USER_ID = 1942;
    private const CREATED_AT = '2026-03-19 00:00:00';

    public function load(ObjectManager $manager): void
    {
        if (! $manager->getConnection()->createSchemaManager()->tablesExist(['admin_user'])) {
            return;
        }

        $repository = $manager->getRepository(AdminUser::class);

        if ($repository instanceof AdminUserRepository && $repository->hasUserId(self::ADMIN_USER_ID)) {
            return;
        }

        $manager->persist(new AdminUser(self::ADMIN_USER_ID, new DateTimeImmutable(self::CREATED_AT)));
        $manager->flush();
    }
}
