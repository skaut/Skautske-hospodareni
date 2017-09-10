<?php

namespace Model\Payment\Repositories;

use Assert\Assert;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\Utils\Arrays;

class PaymentRepository implements IPaymentRepository
{

    /** @var EntityManager */
    private $em;

    private const STATE_ORDER = [
        State::PREPARING,
        State::SENT,
        State::COMPLETED,
        State::CANCELED,
    ];

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id): Payment
    {
        $payment = $this->em->find(Payment::class, $id);

        if (!$payment instanceof Payment) {
            throw new PaymentNotFoundException();
        }

        return $payment;
    }

    public function findByGroup(int $groupId): array
    {
        return $this->findByMultipleGroups([$groupId])[$groupId];
    }

    public function findByMultipleGroups(array $groupIds): array
    {
        Assert::thatAll($groupIds)->integer();

        if(empty($groupIds)) {
            return [];
        }

        $result = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->where('IDENTITY(p.group) IN (:groupIds)')
            ->orderBy('FIELD (p.state, :states)')
            ->setParameter('groupIds', $groupIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('states', self::STATE_ORDER, Connection::PARAM_STR_ARRAY)
            ->getQuery()->getResult();

        return Arrays::groupBy($result, function(Payment $p) { return $p->getGroupId(); }) + array_fill_keys($groupIds, []);
    }

    public function save(Payment $payment): void
    {
        $this->em->persist($payment);
        $this->em->flush();
    }

    public function saveMany(array $payments): void
    {
        if (empty($payments)) {
            return;
        }

        Assert::thatAll($payments)->isInstanceOf(Payment::class);

        foreach($payments as $payment) {
            $this->em->persist($payment);
        }
        $this->em->flush();
    }

    public function getMaxVariableSymbol(int $groupId): ?int
    {
        return $this->em->createQueryBuilder()
            ->select("MAX(p.variableSymbol)")
            ->from(Payment::class, "p")
            ->where("IDENTITY(p.group) = :groupId")
            ->andWhere("p.state != :state")
            ->setParameter("groupId", $groupId)
            ->setParameter("state", State::CANCELED)
            ->getQuery()
            ->getSingleScalarResult();
    }

}
