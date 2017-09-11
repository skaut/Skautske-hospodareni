<?php

namespace Model\Payment\Repositories;

use Assert\Assert;
use Doctrine\DBAL\Connection;
use Model\Infrastructure\Repositories\AbstractRepository;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\Payment\Summary;
use Model\Utils\Arrays;

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
            ->select("IDENTITY(p.group) as groupId, p.state as state, SUM(p.amount) as amount, COUNT(p.id) as number")
            ->from(Payment::class, "p")
            ->where("IDENTITY(p.group) IN (:ids)")
            ->groupBy("groupId, state")
            ->having("state IN (:states)")
            ->setParameter("ids", $groupIds, Connection::PARAM_STR_ARRAY)
            ->setParameter("states", $states, Connection::PARAM_STR_ARRAY)
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
        return $this->findByMultipleGroups([$groupId])[$groupId];
    }

    public function findByMultipleGroups(array $groupIds): array
    {
        Assert::thatAll($groupIds)->integer();

        if(empty($groupIds)) {
            return [];
        }

        $result = $this->getEntityManager()->createQueryBuilder()
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

    public function getMaxVariableSymbol(int $groupId): ?int
    {
        return $this->getEntityManager()->createQueryBuilder()
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
