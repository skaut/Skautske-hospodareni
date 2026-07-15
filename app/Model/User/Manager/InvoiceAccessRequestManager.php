<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\Entity\InvoiceAccessRequest;
use App\Model\User\Entity\InvoiceAccessUser;
use App\Model\User\Repository\InvoiceAccessRequestRepository;
use App\Model\User\Repository\InvoiceAccessUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceAccessRequestManager extends AbstractManager
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private InvoiceAccessRequestRepository $requestRepository,
        private InvoiceAccessUserRepository $accessUserRepository,
    ) {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceAccessRequest::class;
    }

    public function createRequest(
        int $userId,
        ?int $unitId,
        ?int $roleId,
        string $displayName,
        ?string $requesterEmail,
        string $note,
    ): InvoiceAccessRequest {
        return $this->em->wrapInTransaction(function () use ($userId, $unitId, $roleId, $displayName, $requesterEmail, $note): InvoiceAccessRequest {
            $existingRequest = $this->requestRepository->findOpenByUserId($userId);
            if ($existingRequest instanceof InvoiceAccessRequest) {
                return $existingRequest;
            }

            $request = new InvoiceAccessRequest($userId, $unitId, $roleId, $displayName, $requesterEmail, $note);
            $this->em->persist($request);
            $this->em->flush();

            return $request;
        });
    }

    public function approve(InvoiceAccessRequest $request): void
    {
        $this->em->wrapInTransaction(function () use ($request): void {
            if (! $this->accessUserRepository->hasUserId($request->getUserId())) {
                $this->em->persist(new InvoiceAccessUser($request->getUserId()));
            }

            $request->approve();
            $this->em->persist($request);
            $this->em->flush();
        });
    }

    public function reject(InvoiceAccessRequest $request): void
    {
        $this->em->wrapInTransaction(function () use ($request): void {
            $request->reject();
            $this->em->persist($request);
            $this->em->flush();
        });
    }
}
