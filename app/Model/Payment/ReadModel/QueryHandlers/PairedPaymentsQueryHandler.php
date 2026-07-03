<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment\PaymentFactory;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\ReadModel\Queries\PairedPaymentsQuery;
use Doctrine\ORM\EntityManager;

use function array_map;

class PairedPaymentsQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    /** @return \App\Model\DTO\Payment\Payment[] */
    public function __invoke(PairedPaymentsQuery $query): array
    {
        $groupIds = $this->entityManager->createQueryBuilder()
            ->select('g.id')
            ->from(Group::class, 'g')
            ->where('g.bankAccount.id = :bankAccountId')
            ->getDQL();

        $queryBuilder = $this->entityManager->createQueryBuilder();

        $payments = $queryBuilder->select('p')
            ->from(Payment::class, 'p')
            ->where($queryBuilder->expr()->in('p.groupId', $groupIds))
            ->andWhere('p.transaction.id IS NOT NULL')
            ->andWhere('p.closedAt BETWEEN :since AND :until')
            ->setParameter('bankAccountId', $query->getBankAccountId()->toInt())
            ->setParameters([
                'bankAccountId' => $query->getBankAccountId()->toInt(),
                'since' => $query->getSince(),
                'until' => $query->getUntil(),
            ])
            ->getQuery()
            ->getResult();

        return array_map([PaymentFactory::class, 'create'], $payments);
    }
}
