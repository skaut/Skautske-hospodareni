<?php

namespace Model\Payment\Repositories;

use Consistence\Type\ArrayType\ArrayType;
use Doctrine\DBAL\Connection;
use Kdyby\Doctrine\EntityManager;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\Payment\Summary;

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

    public function summarizeByGroup(array $groupIds): array
    {
        $states = [State::PREPARING, State::SENT, State::CANCELED];

        $res = $this->em->createQueryBuilder()
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
            $counts[$id][$row["state"]]++;
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
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->where('IDENTITY(p.group) = :groupId')
            ->orderBy('FIELD (p.state, :states)')
            ->setParameter('groupId', $groupId)
            ->setParameter('states', self::STATE_ORDER, Connection::PARAM_STR_ARRAY)
            ->getQuery()->getResult();
    }

    public function save(Payment $payment): void
    {
        $this->em->persist($payment)->flush();
    }

    public function saveMany(array $payments): void
    {
        if (empty($payments)) {
            return;
        }

        $filtered = ArrayType::filterValuesByCallback($payments, function ($payment) {
            return $payment instanceof Payment;
        });

        if (count($filtered) !== count($payments)) {
            throw new \InvalidArgumentException("Expected array of payments");
        }

        $this->em->persist($filtered)->flush();
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
