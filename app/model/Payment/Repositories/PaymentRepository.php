<?php

namespace Model\Payment\Repositories;

use Consistence\Type\ArrayType\ArrayType;
use Doctrine\DBAL\Connection;
use Kdyby\Doctrine\EntityManager;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;

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
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->join('p.group', 'g')
            ->where('g.id = :groupId')
            ->orderBy('FIELD (g.state, :states)')
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


}
