<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Assert\Assert;
use Model\Infrastructure\Repositories\AggregateRepository;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFound;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;
use function array_fill_keys;

final class PaymentRepository extends AggregateRepository implements IPaymentRepository
{
    private const STATE_ORDER = [
        State::PREPARING,
        State::SENT,
        State::COMPLETED,
        State::CANCELED,
    ];

    public function find(int $id) : Payment
    {
        $payment = $this->getEntityManager()->find(Payment::class, $id);

        if (! $payment instanceof Payment) {
            throw new PaymentNotFound();
        }

        return $payment;
    }

    /**
     * {@inheritDoc}
     */
    public function summarizeByGroup(array $groupIds) : array
    {
        $states = [State::PREPARING, State::SENT, State::COMPLETED];

        $res = $this->getEntityManager()->createQueryBuilder()
            ->select('p.groupId as groupId, p.state as state, SUM(p.amount) as amount, COUNT(p.id) as number')
            ->from(Payment::class, 'p')
            ->where('p.groupId IN (:ids)')
            ->groupBy('groupId, state')
            ->having('state IN (:states)')
            ->setParameter('ids', $groupIds)
            ->setParameter('states', $states)
            ->getQuery()
            ->getResult();

        $amounts = array_fill_keys($groupIds, array_fill_keys($states, 0));
        $counts  = array_fill_keys($groupIds, array_fill_keys($states, 0));

        foreach ($res as $row) {
            $id                           = (int) $row['groupId'];
            $amounts[$id][$row['state']] += (float) $row['amount'];
            $counts[$id][$row['state']]   = (int) $row['number'];
        }

        $summaries = array_fill_keys($groupIds, []);

        foreach ($groupIds as $id) {
            foreach ($states as $state) {
                $summaries[$id][$state] = new Summary($counts[$id][$state], $amounts[$id][$state]);
            }
        }

        return $summaries;
    }

    /**
     * {@inheritDoc}
     */
    public function findByGroup(int $groupId) : array
    {
        return $this->findByMultipleGroups([$groupId]);
    }

    /**
     * {@inheritDoc}
     */
    public function findByMultipleGroups(array $groupIds) : array
    {
        Assert::thatAll($groupIds)->integer();

        if (empty($groupIds)) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('p, e')
            ->from(Payment::class, 'p')
            ->leftJoin('p.sentEmails', 'e')
            ->where('p.groupId IN (:groupIds)')
            ->orderBy('FIELD (p.state, :states)')
            ->addOrderBy('p.id')
            ->setParameter('groupIds', $groupIds)
            ->setParameter('states', self::STATE_ORDER)
            ->getQuery()
            ->getResult();
    }

    public function save(Payment $payment) : void
    {
        $this->saveAndDispatchEvents($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function saveMany(array $payments) : void
    {
        if (empty($payments)) {
            return;
        }

        Assert::thatAll($payments)->isInstanceOf(Payment::class);

        foreach ($payments as $payment) {
            $this->saveAndDispatchEvents($payment);
        }
    }

    public function remove(Payment $payment) : void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->remove($payment);
        $entityManager->flush();
    }

    public function getMaxVariableSymbol(int $groupId) : ?VariableSymbol
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(p.variableSymbol) as vs')
            ->from(Payment::class, 'p')
            ->where('p.groupId = :groupId')
            ->andWhere('p.state != :state')
            ->setParameter('groupId', $groupId)
            ->setParameter('state', State::CANCELED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null
            ? new VariableSymbol($result)
            : null;
    }
}
