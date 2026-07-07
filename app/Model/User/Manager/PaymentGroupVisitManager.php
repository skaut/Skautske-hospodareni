<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\Entity\PaymentGroupVisit;
use App\Model\User\Repository\PaymentGroupVisitRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class PaymentGroupVisitManager extends AbstractManager
{
    private const MAX_VISITS_PER_USER = 3;

    public function __construct(
        EntityManagerInterface $entityManager,
        private PaymentGroupVisitRepository $repository,
    ) {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return PaymentGroupVisit::class;
    }

    public function markVisited(int $userId, int $groupId): PaymentGroupVisit
    {
        return $this->em->wrapInTransaction(function () use ($userId, $groupId): PaymentGroupVisit {
            $visit = $this->repository->findOneByUserIdAndGroupId($userId, $groupId)
                ?? new PaymentGroupVisit($userId, $groupId);

            $visit->markVisited(new DateTimeImmutable());
            $this->em->persist($visit);
            $this->em->flush();
            $this->repository->deleteVisitsOverLimit($userId, self::MAX_VISITS_PER_USER);

            return $visit;
        });
    }
}
