<?php

namespace Model\Payment\Repositories;

use Kdyby\Doctrine\EntityManager;
use Model\Payment\Payment;
use Model\Payment\PaymentNotFoundException;

class PaymentRepository implements IPaymentRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

	public function find(int $id): Payment
	{
		$payment = $this->em->find(Payment::class, $id);

		if(! $payment instanceof Payment) {
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
            ->setParameter('groupId', $groupId)
            ->getQuery()
            ->getResult();
    }

    public function save(Payment $payment): void
    {
        $this->em->persist($payment)->flush();
    }

}
