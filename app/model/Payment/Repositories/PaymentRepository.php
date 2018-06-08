<?php

namespace Model\Payment\Repositories;

use Assert\Assert;
use Model\Infrastructure\Repositories\AbstractRepository;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;

final class PaymentRepository extends AbstractRepository implements IPaymentRepository
{

    private const STATE_ORDER = [
        State::PREPARING,
        State::SENT,
        State::COMPLETED,
        State::CANCELED,
    ];


    public function find(int $id): Payment
    {
        $payment = $this->getEntityManager()->find(Payment::class, $id);

        if (!$payment instanceof Payment) {
            throw new PaymentNotFoundException();
        }

        return $payment;
    }

    public function summarizeByGroup(array $groupIds): array
    {
        $states = [State::PREPARING, State::SENT, State::COMPLETED];

        $res = $this->getEntityManager()->createQueryBuilder()
            ->select("p.groupId as groupId, p.state as state, SUM(p.amount) as amount, COUNT(p.id) as number")
            ->from(Payment::class, "p")
            ->where("p.groupId IN (:ids)")
            ->groupBy("groupId, state")
            ->having("state IN (:states)")
            ->setParameter("ids", $groupIds)
            ->setParameter("states", $states)
            ->getQuery()
            ->getResult();

        $amounts = array_fill_keys($groupIds, array_fill_keys($states, 0));
        $counts = array_fill_keys($groupIds, array_fill_keys($states, 0));

        foreach($res as $row) {
            $id = (int)$row["groupId"];
            $amounts[$id][$row["state"]] += (float)$row["amount"];
            $counts[$id][$row["state"]] = (int)$row["number"];
        }

        $summaries = array_fill_keys($groupIds, []);

        foreach($groupIds as $id) {
            foreach($states as $state) {
                $summaries[$id][$state] = new Summary($counts[$id][$state], $amounts[$id][$state]);
            }
        }

        return $summaries;
    }

    public function findByGroup(int $groupId): array
    {
        return $this->findByMultipleGroups([$groupId]);
    }

    public function findByMultipleGroups(array $groupIds): array
    {
        Assert::thatAll($groupIds)->integer();

        if(empty($groupIds)) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->where('p.groupId IN (:groupIds)')
            ->orderBy('FIELD (p.state, :states)')
            ->setParameter('groupIds', $groupIds)
            ->setParameter('states', self::STATE_ORDER)
            ->getQuery()
            ->getResult();
    }

    public function save(Payment $payment): void
    {
        $this->saveAndDispatchEvents($payment);
    }

    public function saveMany(array $payments): void
    {
        if (empty($payments)) {
            return;
        }

        Assert::thatAll($payments)->isInstanceOf(Payment::class);

        foreach($payments as $payment) {
            $this->saveAndDispatchEvents($payment);
        }
    }

    public function remove(Payment $payment): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->remove($payment);
        $entityManager->flush();
    }

    public function getMaxVariableSymbol(int $groupId): ?VariableSymbol
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select("MAX(p.variableSymbol) as vs")
            ->from(Payment::class, "p")
            ->where("p.groupId = :groupId")
            ->andWhere("p.state != :state")
            ->setParameter("groupId", $groupId)
            ->setParameter("state", State::CANCELED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== NULL
            ? new VariableSymbol($result)
            : NULL;
    }

}
