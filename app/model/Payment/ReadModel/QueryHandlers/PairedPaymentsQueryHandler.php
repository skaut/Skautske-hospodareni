<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\DTO\Payment\PaymentFactory;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\ReadModel\Queries\PairedPaymentsQuery;
use function array_map;

class PairedPaymentsQueryHandler
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Model\DTO\Payment\Payment[]
     */
    public function __invoke(PairedPaymentsQuery $query) : array
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
