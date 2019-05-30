<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Participant;

use Doctrine\ORM\EntityManager;
use Model\Participant\Payment;
use Model\Participant\Payment\Event;
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
    public function findByEvent(Event $event) : array
    {
        return $this->em
            ->createQuery(<<<'DQL'
                SELECT p FROM Model\Participant\Payment p INDEX BY p.participantId
                    WHERE p.event.id = :eventId AND p.event.type = :eventType
            DQL)
            ->execute([
                'eventId' => $event->getId(),
                'eventType' => $event->getType()->toString(),
            ]);
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
