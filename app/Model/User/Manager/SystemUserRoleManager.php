<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\Entity\SystemUserRole;
use App\Model\User\Enum\SystemRole;
use App\Model\User\Repository\SystemUserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SystemUserRoleManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager, private SystemUserRoleRepository $repository)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return SystemUserRole::class;
    }

    /** @param SystemRole[] $roles */
    public function replaceRoles(int $userId, array $roles): void
    {
        $rolesByValue = [];
        foreach ($roles as $role) {
            $rolesByValue[$role->value] = $role;
        }

        $this->em->wrapInTransaction(function () use ($userId, $rolesByValue): void {
            $existingByRole = [];
            foreach ($this->repository->findByUserId($userId) as $assignment) {
                $existingByRole[$assignment->getRole()->value] = $assignment;
            }

            foreach ($existingByRole as $value => $assignment) {
                if (! isset($rolesByValue[$value])) {
                    $this->em->remove($assignment);
                }
            }

            foreach ($rolesByValue as $value => $role) {
                if (! isset($existingByRole[$value])) {
                    $this->em->persist(new SystemUserRole($userId, $role));
                }
            }

            $this->em->flush();
        });
    }

    public function removeAllForUser(int $userId): void
    {
        $this->replaceRoles($userId, []);
    }
}
