<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Participant;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Model\Participant\Payment;
use Model\Participant\Payment\Event;
use Model\Participant\PaymentNotFound;
use Model\Participant\Repositories\IPaymentRepository;

final class PaymentRepository implements IPaymentRepository
{
    public function __construct(private EntityManager $em)
    {
    }

    public function findByParticipant(int $participantId, Payment\EventType $eventType): Payment
    {
        try {
            $payment = $this->em->createQueryBuilder()
                ->select('p')
                ->from(Payment::class, 'p')
                ->where('p.participantId = :participantId')
                ->andWhere('p.event.type = :event_type')
                ->setParameter('participantId', $participantId)
                ->setParameter('event_type', $eventType->toString())
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            throw new PaymentNotFound();
        }

        return $payment;
    }

    /** @return Payment[] */
    public function findByEvent(Event $event): array
    {
        return $this->em
            ->createQuery(<<<'DQL'
                SELECT p FROM Model\Participant\Payment p INDEX BY p.participantId
                    WHERE p.event.id = :eventId AND p.event.type = :eventType
            DQL)
            ->setParameter('eventId', $event->getId())
            ->setParameter('eventType', $event->getType()->toString())
            ->getResult();
    }

    public function save(Payment $payment): void
    {
        $this->em->persist($payment);
        $this->em->flush();
    }

    public function remove(Payment $payment): void
    {
        $this->em->remove($payment);
        $this->em->flush();
    }
}
