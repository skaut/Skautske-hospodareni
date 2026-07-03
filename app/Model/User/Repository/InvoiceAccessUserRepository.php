<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\User\Entity\InvoiceAccessUser;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceAccessUserRepository extends AbstractRepository
{
    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceAccessUser::class;
    }

    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist(['invoice_access_user']);
    }

    /** @return InvoiceAccessUser[] */
    public function findAllOrderedByUserId(): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        return $this->findBy([], ['userId' => 'ASC']);
    }

    public function findOneByUserId(int $userId): ?InvoiceAccessUser
    {
        if (! $this->isStorageAvailable()) {
            return null;
        }

        /** @var InvoiceAccessUser|null $accessUser */
        $accessUser = $this->findOneBy(['userId' => $userId]);

        return $accessUser;
    }

    public function hasUserId(int $userId): bool
    {
        return $this->findOneByUserId($userId) instanceof InvoiceAccessUser;
    }
}
