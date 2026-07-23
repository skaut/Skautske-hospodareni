<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\User\Entity\InvoiceAccessRequest;
use Doctrine\ORM\EntityManagerInterface;

/** @extends AbstractRepository<InvoiceAccessRequest> */
class InvoiceAccessRequestRepository extends AbstractRepository
{
    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceAccessRequest::class;
    }

    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist(['invoice_access_request']);
    }

    /** @return InvoiceAccessRequest[] */
    public function findOpenOrderedByCreatedAt(): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        return $this->findBy(['state' => InvoiceAccessRequest::STATE_OPEN], ['createdAt' => 'ASC']);
    }

    public function findOpenByUserId(int $userId): ?InvoiceAccessRequest
    {
        if (! $this->isStorageAvailable()) {
            return null;
        }

        /** @var InvoiceAccessRequest|null $request */
        $request = $this->findOneBy([
            'userId' => $userId,
            'state' => InvoiceAccessRequest::STATE_OPEN,
        ]);

        return $request;
    }
}
