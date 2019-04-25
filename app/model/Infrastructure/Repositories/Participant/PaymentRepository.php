<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Participant;

use Doctrine\ORM\EntityManager;
use Model\Event\SkautisEventId;
use Model\Participant\Payment;
use Model\Participant\PaymentNotFound;
use Model\Participant\Repositories\IPaymentRepository;

final class PaymentRepository implements IPaymentRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id) : Payment
    {
        $payment = $this->em->find(Payment::class, $id);
        if ($payment === null) {
            throw new PaymentNotFound();
        }

        return $payment;
    }

    /**
     * @return Payment[]
     */
    public function findByEvent(SkautisEventId $actionId) : array
    {
        $res      = [];
        $payments = $this->em->getRepository(Payment::class)->findBy(['actionId' => $actionId->toInt()]);
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $res[$payment->getParticipantId()] = $payment;
        }

        return $res;
    }

    public function save(Payment $payment) : void
    {
        $this->em->persist($payment);
        $this->em->flush();
    }

    public function remove(Payment $payment) : void
    {
        $this->em->remove($payment);
        $this->em->flush();
    }
}
