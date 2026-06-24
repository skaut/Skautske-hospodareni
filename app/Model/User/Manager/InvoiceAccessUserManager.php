<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\Entity\InvoiceAccessUser;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceAccessUserManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceAccessUser::class;
    }

    public function create(int $userId): InvoiceAccessUser
    {
        return $this->em->wrapInTransaction(function () use ($userId): InvoiceAccessUser {
            $accessUser = new InvoiceAccessUser($userId);
            $this->em->persist($accessUser);
            $this->em->flush();

            return $accessUser;
        });
    }

    public function delete(InvoiceAccessUser $accessUser): void
    {
        $this->em->wrapInTransaction(function () use ($accessUser): void {
            $this->em->remove($accessUser);
            $this->em->flush();
        });
    }
}
