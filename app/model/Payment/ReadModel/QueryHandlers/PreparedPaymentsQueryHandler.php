<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\DTO\Payment\PaymentFactory;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\ReadModel\Queries\PreparedPairedPaymentsQuery;

use function array_map;

class PreparedPaymentsQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    /** @return \Model\DTO\Payment\Payment[] */
    public function __invoke(PreparedPairedPaymentsQuery $query): array
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
            ->andWhere('p.state = :state')
            ->setParameters([
                'state' => State::PREPARING,
                'bankAccountId' => $query->getBankAccountId()->toInt(),
            ])
            ->getQuery()
            ->getResult();

        return array_map([PaymentFactory::class, 'create'], $payments);
    }
}
