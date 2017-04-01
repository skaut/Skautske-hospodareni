<?php

namespace Model\Payment\Repositories;

use Kdyby\Doctrine\EntityManager;
use Model\Payment\Payment;

class PaymentRepository implements IPaymentRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function save(Payment $payment): void
    {
        $this->em->persist($payment)->flush();
    }

}
