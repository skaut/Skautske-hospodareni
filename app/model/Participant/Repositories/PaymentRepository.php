<?php

declare(strict_types=1);

namespace Model\Budget\Repositories;

use Doctrine\ORM\EntityManager;
use Model\Participant\Payment;
use Model\Participant\PaymentNofFound;

class PaymentRepository implements IPaymentRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function findPayment(int $id) : Payment
    {
        $payment = $this->em->find(Payment::class, $id);
        if ($payment === null) {
            throw new PaymentNofFound();
        }
        return $payment;
    }


    public function savePayment(Payment $payment) : void
    {
        $this->em->persist($payment);
        $this->em->flush();
    }
}
