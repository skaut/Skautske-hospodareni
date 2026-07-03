<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment\PaymentFactory;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Payment\State;
use App\Model\Payment\ReadModel\Queries\PreparedPairedPaymentsQuery;
use Doctrine\ORM\EntityManager;

use function array_map;

class PreparedPaymentsQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    /** @return \App\Model\DTO\Payment\Payment[] */
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
